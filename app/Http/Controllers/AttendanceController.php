<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceException;
use App\Http\Requests\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function index(AttendanceFilterRequest $request): View
    {
        $user = $request->user();
        $filters = $request->validated();

        $records = Attendance::query()
            ->with('employee.department')
            ->forFilters($filters, $user)
            ->orderByDesc('attendance_date')
            ->orderByDesc('check_in_at')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        $employees = $user->isAdmin()
            ? Employee::orderBy('name')->get(['id', 'name', 'employee_code'])
            : collect();

        // Absent list: admins only, for one specific day (defaults to today).
        $absentDate = $filters['date'] ?? ($filters['from'] ?? null) ?? Carbon::today()->toDateString();
        $absentEmployees = collect();

        if ($user->isAdmin() && empty($filters['employee_id'])) {
            $absentEmployees = Employee::query()
                ->with('department')
                ->where('status', Employee::STATUS_ACTIVE)
                ->whereDoesntHave('attendance', fn ($q) => $q->whereDate('attendance_date', $absentDate))
                ->orderBy('name')
                ->get();
        }

        $linkedEmployee = $user->employee;

        return view('attendance.index', [
            'records' => $records,
            'employees' => $employees,
            'absentEmployees' => $absentEmployees,
            'absentDate' => $absentDate,
            'filters' => $filters,
            'linkedEmployee' => $linkedEmployee,
            'todayRecord' => $linkedEmployee
                ? Attendance::where('employee_id', $linkedEmployee->id)
                    ->whereDate('attendance_date', Carbon::today())
                    ->first()
                : null,
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        return $this->runAction($request, fn (Employee $employee) => $this->attendance->checkIn($employee), 'Check-in recorded.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        return $this->runAction($request, fn (Employee $employee) => $this->attendance->checkOut($employee), 'Check-out recorded.');
    }

    private function runAction(Request $request, callable $action, string $successMessage): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->with('error', 'Your login is not linked to an employee profile. Ask the administrator.');
        }

        try {
            $action($employee);
        } catch (AttendanceException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }
}
