<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        [$user] = $this->employeeWithLogin();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'token_type', 'user'], 'errors']);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_wrong_password_with_401_and_json_body(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Invalid email or password.']);
    }

    public function test_login_validates_input_with_422(): void
    {
        $this->postJson('/api/login', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_deactivated_employee_cannot_get_a_token(): void
    {
        [$user, $employee] = $this->employeeWithLogin();
        $employee->update(['status' => 'inactive']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertStatus(403);
    }

    public function test_protected_endpoints_require_a_token(): void
    {
        $this->getJson('/api/employees')->assertStatus(401)->assertJsonPath('success', false);
        $this->getJson('/api/attendance')->assertStatus(401);
        $this->postJson('/api/attendance/check-in')->assertStatus(401);
    }

    public function test_admin_can_list_employees_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        Employee::factory()->count(3)->create();

        $this->getJson('/api/employees', $this->bearer($this->tokenFor($admin)))
            ->assertOk()
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_employee_cannot_list_all_employees(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->getJson('/api/employees', $this->bearer($this->tokenFor($user)))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_employee_can_view_own_record_but_not_someone_elses(): void
    {
        [$me, $myEmployee] = $this->employeeWithLogin();
        [, $other] = $this->employeeWithLogin();

        $this->getJson('/api/employees/'.$myEmployee->id, $this->bearer($this->tokenFor($me)))
            ->assertOk()
            ->assertJsonPath('data.id', $myEmployee->id);

        $this->getJson('/api/employees/'.$other->id, $this->bearer($this->tokenFor($me)))
            ->assertStatus(403);
    }

    public function test_missing_employee_returns_404_json(): void
    {
        $admin = User::factory()->admin()->create();

        $this->getJson('/api/employees/999999', $this->bearer($this->tokenFor($admin)))
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_employee_can_check_in_and_out_via_api(): void
    {
        [$user, $employee] = $this->employeeWithLogin();
        $headers = $this->bearer($this->tokenFor($user));

        $this->postJson('/api/attendance/check-in', [], $headers)
            ->assertStatus(201)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.check_out_at', null);

        $this->postJson('/api/attendance/check-out', [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(Attendance::where('employee_id', $employee->id)->first()->check_out_at);
    }

    public function test_duplicate_check_in_returns_409(): void
    {
        [$user] = $this->employeeWithLogin();
        $headers = $this->bearer($this->tokenFor($user));

        $this->postJson('/api/attendance/check-in', [], $headers)->assertStatus(201);

        $this->postJson('/api/attendance/check-in', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_check_out_without_check_in_returns_422(): void
    {
        [$user] = $this->employeeWithLogin();

        $this->postJson('/api/attendance/check-out', [], $this->bearer($this->tokenFor($user)))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_without_employee_profile_cannot_check_in(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/attendance/check-in', [], $this->bearer($this->tokenFor($admin)))
            ->assertStatus(403);
    }

    public function test_employee_attendance_list_contains_only_own_rows(): void
    {
        [$me, $myEmployee] = $this->employeeWithLogin();
        [, $other] = $this->employeeWithLogin();

        Attendance::factory()->create(['employee_id' => $myEmployee->id]);
        Attendance::factory()->create(['employee_id' => $other->id]);

        $this->getJson('/api/attendance', $this->bearer($this->tokenFor($me)))
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.employee_id', $myEmployee->id);
    }

    public function test_api_rejects_invalid_attendance_filters_with_422(): void
    {
        $admin = User::factory()->admin()->create();

        $this->getJson('/api/attendance?date=10-10-2026', $this->bearer($this->tokenFor($admin)))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_deactivated_user_token_is_refused(): void
    {
        [$user] = $this->employeeWithLogin();
        $headers = $this->bearer($this->tokenFor($user));
        $user->update(['is_active' => false]);

        $this->getJson('/api/attendance', $headers)->assertStatus(403);
    }
}
