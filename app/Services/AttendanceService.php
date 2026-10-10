<?php

namespace App\Services;

use App\Exceptions\AttendanceException;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class AttendanceService
{
    /**
     * Record today's check-in. Only one check-in per employee per day.
     *
     * @throws AttendanceException
     */
    public function checkIn(Employee $employee): Attendance
    {
        $now = Carbon::now();

        if (! $employee->isActive()) {
            throw new AttendanceException('Your employee record is inactive.', 403);
        }

        if ($this->todayRecord($employee, $now) !== null) {
            throw new AttendanceException('You have already checked in today.', 409);
        }

        try {
            return Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'check_in_at' => $now,
            ]);
        } catch (QueryException $e) {
            // The unique (employee_id, attendance_date) index catches simultaneous double-clicks.
            throw new AttendanceException('You have already checked in today.', 409);
        }
    }

    /**
     * Record today's check-out. Requires a check-in today and no earlier check-out.
     *
     * @throws AttendanceException
     */
    public function checkOut(Employee $employee): Attendance
    {
        $now = Carbon::now();
        $record = $this->todayRecord($employee, $now);

        if ($record === null) {
            throw new AttendanceException('You cannot check out before checking in today.', 422);
        }

        if ($record->check_out_at !== null) {
            throw new AttendanceException('You have already checked out today.', 409);
        }

        $record->update(['check_out_at' => $now]);

        return $record->refresh();
    }

    private function todayRecord(Employee $employee, Carbon $now): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();
    }
}
