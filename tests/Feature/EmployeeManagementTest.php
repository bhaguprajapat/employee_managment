<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validEmployeePayload(Department $department, array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 'EMP1001',
            'name' => 'Asha Verma',
            'email' => 'asha.verma@example.com',
            'mobile_number' => '9876543210',
            'department_id' => $department->id,
            'designation' => 'Software Engineer',
            'joining_date' => '2024-04-01',
            'status' => 'active',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ], $overrides);
    }

    public function test_admin_can_create_an_employee_with_a_login(): void
    {
        $admin = $this->admin();
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->post(route('employees.store'), $this->validEmployeePayload($department))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP1001',
            'email' => 'asha.verma@example.com',
            'status' => 'active',
        ]);

        $employee = Employee::where('employee_code', 'EMP1001')->firstOrFail();
        $this->assertNotNull($employee->user_id);

        $login = User::find($employee->user_id);
        $this->assertSame('employee', $login->role);
        $this->assertTrue($login->is_active);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret-pass-1', $login->password));
    }

    public function test_employee_creation_validates_required_fields_and_duplicates(): void
    {
        $admin = $this->admin();
        $department = Department::factory()->create();
        Employee::factory()->create(['employee_code' => 'EMP1001', 'email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->from(route('employees.create'))
            ->post(route('employees.store'), [
                'employee_code' => 'EMP1001',
                'email' => 'taken@example.com',
                'mobile_number' => 'abc',
                'department_id' => $department->id,
                'joining_date' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['name', 'employee_code', 'email', 'mobile_number', 'designation', 'joining_date', 'password']);

        $this->assertDatabaseMissing('users', ['email' => 'taken@example.com']);
    }

    public function test_employee_cannot_open_the_employee_pages(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->actingAs($user)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($user)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($user)->post(route('employees.store'), [])->assertForbidden();
    }

    public function test_employee_cannot_see_the_admin_dashboard(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_admin_can_deactivate_and_reactivate_an_employee(): void
    {
        $admin = $this->admin();
        [$user, $employee] = $this->employeeWithLogin();

        $this->actingAs($admin)
            ->patch(route('employees.toggle-status', $employee))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $employee->fresh()->status);
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)->patch(route('employees.toggle-status', $employee));

        $this->assertSame('active', $employee->fresh()->status);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_employee_cannot_toggle_their_own_status(): void
    {
        [$user, $employee] = $this->employeeWithLogin();

        $this->actingAs($user)
            ->patch(route('employees.toggle-status', $employee))
            ->assertForbidden();

        $this->assertSame('active', $employee->fresh()->status);
    }

    public function test_admin_can_search_and_filter_employees(): void
    {
        $admin = $this->admin();
        Employee::factory()->create(['name' => 'Ravi Kumar', 'employee_code' => 'EMP2001', 'email' => 'ravi@example.com']);
        Employee::factory()->inactive()->create(['name' => 'Meena Iyer', 'employee_code' => 'EMP2002', 'email' => 'meena@example.com']);

        $this->actingAs($admin)
            ->get(route('employees.index', ['q' => 'Ravi']))
            ->assertOk()
            ->assertSee('Ravi Kumar')
            ->assertDontSee('Meena Iyer');

        $this->actingAs($admin)
            ->get(route('employees.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Meena Iyer')
            ->assertDontSee('Ravi Kumar');
    }
}
