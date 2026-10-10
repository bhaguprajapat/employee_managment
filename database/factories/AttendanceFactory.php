<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $day = Carbon::today();
        $checkIn = $day->copy()->setTime(9, 0);

        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => $day->toDateString(),
            'check_in_at' => $checkIn,
            'check_out_at' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(function (array $attributes) {
            $checkIn = Carbon::parse($attributes['check_in_at']);

            return ['check_out_at' => $checkIn->copy()->addHours(8)];
        });
    }
}
