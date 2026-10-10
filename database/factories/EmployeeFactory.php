<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'employee_code' => 'EMP'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'email' => $email,
            'mobile_number' => fake()->numerify('98########'),
            'department_id' => Department::factory(),
            'designation' => fake()->jobTitle(),
            'joining_date' => fake()->dateTimeBetween('-5 years', '-1 day')->format('Y-m-d'),
            'status' => Employee::STATUS_ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Employee::STATUS_INACTIVE]);
    }
}
