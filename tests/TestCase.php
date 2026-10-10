<?php

namespace Tests;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected const PASSWORD = 'password123';

    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    /**
     * Creates an employee profile plus its login account, linked together.
     *
     * @return array{0: User, 1: Employee}
     */
    protected function employeeWithLogin(array $employeeAttributes = [], array $userAttributes = []): array
    {
        $department = Department::factory()->create();

        $user = User::factory()->create(array_merge([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ], $userAttributes));

        $employee = Employee::factory()->create(array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => $department->id,
            'status' => Employee::STATUS_ACTIVE,
        ], $employeeAttributes));

        // user_id is intentionally not mass-assignable, so it is set explicitly here.
        $employee->forceFill(['user_id' => $user->id])->save();

        return [$user, $employee->refresh()];
    }
}
