<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today()->toDateString();

        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', Employee::STATUS_ACTIVE)->count();

        // Present = an active employee who has checked in today.
        $presentToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereHas('employee', fn ($q) => $q->where('status', Employee::STATUS_ACTIVE))
            ->count();

        // Absent = an active employee with no check-in today.
        $absentToday = max($activeEmployees - $presentToday, 0);

        $recentAttendance = Attendance::query()
            ->with('employee:id,name,employee_code')
            ->latest('check_in_at')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'presentToday',
            'absentToday',
            'recentAttendance',
        ));
    }
}
