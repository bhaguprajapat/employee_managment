<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_once_per_day(): void
    {
        [$user, $employee] = $this->employeeWithLogin();

        $this->actingAs($user)->post(route('attendance.check-in'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(
            \Illuminate\Support\Facades\DB::table('attendance')
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', Carbon::today()->toDateString())
                ->exists()
        );

        $this->actingAs($user)->post(route('attendance.check-in'))
            ->assertSessionHas('error', 'You have already checked in today.');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_check_out_without_check_in_is_rejected(): void
    {
        [$user, $employee] = $this->employeeWithLogin();

        $this->actingAs($user)->post(route('attendance.check-out'))
            ->assertSessionHas('error', 'You cannot check out before checking in today.');

        $this->assertDatabaseMissing('attendance', ['employee_id' => $employee->id]);
    }

    public function test_employee_can_check_out_and_working_hours_are_calculated(): void
    {
        [$user, $employee] = $this->employeeWithLogin();

        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $this->actingAs($user)->post(route('attendance.check-in'));

        Carbon::setTestNow(Carbon::today()->setTime(17, 30));
        $this->actingAs($user)->post(route('attendance.check-out'))
            ->assertSessionHas('success');

        Carbon::setTestNow();

        $record = Attendance::where('employee_id', $employee->id)->firstOrFail();

        $this->assertNotNull($record->check_out_at);
        $this->assertSame(510, $record->workingMinutes());
        $this->assertSame('8h 30m', $record->workingHoursLabel());
    }

    public function test_second_check_out_is_rejected(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->actingAs($user)->post(route('attendance.check-in'));
        $this->actingAs($user)->post(route('attendance.check-out'));

        $this->actingAs($user)->post(route('attendance.check-out'))
            ->assertSessionHas('error', 'You have already checked out today.');
    }

    public function test_user_without_employee_profile_cannot_check_in(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('attendance.check-in'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_employee_sees_only_their_own_attendance(): void
    {
        [$me, $myEmployee] = $this->employeeWithLogin();
        [, $colleague] = $this->employeeWithLogin();

        Attendance::factory()->create(['employee_id' => $myEmployee->id]);
        Attendance::factory()->create(['employee_id' => $colleague->id]);

        $response = $this->actingAs($me)->get(route('attendance.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('records'));
        $this->assertSame($myEmployee->id, $response->viewData('records')->getCollection()->first()->employee_id);
    }

    public function test_employee_cannot_see_others_by_passing_an_employee_filter(): void
    {
        [$me, $myEmployee] = $this->employeeWithLogin();
        [, $colleague] = $this->employeeWithLogin();

        Attendance::factory()->create(['employee_id' => $colleague->id]);

        $response = $this->actingAs($me)->get(route('attendance.index', ['employee_id' => $colleague->id]));

        $this->assertCount(0, $response->viewData('records'));
    }

    public function test_admin_sees_all_records_and_can_filter_by_employee_and_date(): void
    {
        $admin = $this->admin();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();

        Attendance::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-10-01', 'check_in_at' => '2026-10-01 09:00:00']);
        Attendance::factory()->create(['employee_id' => $other->id, 'attendance_date' => '2026-10-02', 'check_in_at' => '2026-10-02 09:00:00']);

        $all = $this->actingAs($admin)->get(route('attendance.index'));
        $this->assertCount(2, $all->viewData('records'));

        $filtered = $this->actingAs($admin)->get(route('attendance.index', [
            'employee_id' => $employee->id,
            'from' => '2026-09-30',
            'to' => '2026-10-01',
        ]));
        $this->assertCount(1, $filtered->viewData('records'));
    }

    public function test_admin_sees_absent_employees_for_a_date(): void
    {
        $admin = $this->admin();
        $present = Employee::factory()->create(['name' => 'Present Person']);
        $absent = Employee::factory()->create(['name' => 'Absent Person']);
        Employee::factory()->inactive()->create(['name' => 'Inactive Person']);

        Attendance::factory()->create(['employee_id' => $present->id]);

        $response = $this->actingAs($admin)->get(route('attendance.index'));

        $absentIds = $response->viewData('absentEmployees')->pluck('id')->all();

        $this->assertContains($absent->id, $absentIds);
        $this->assertNotContains($present->id, $absentIds);
        $this->assertCount(1, $absentIds);
    }

    public function test_deactivated_employee_is_signed_out_on_next_request(): void
    {
        [$user, $employee] = $this->employeeWithLogin();

        $this->actingAs($user);
        $employee->update(['status' => 'inactive']);
        $user->update(['is_active' => false]);

        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
