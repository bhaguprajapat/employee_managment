<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    // Every method here is admin-only: the routes use the role:admin middleware.
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $employees = Employee::query()
            ->with('department')
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->input('department_id')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        return view('employees.create', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('password');

        DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $request->validated('password'),
                'role' => User::ROLE_EMPLOYEE,
                'is_active' => $data['status'] === Employee::STATUS_ACTIVE,
            ]);

            $employee = new Employee($data);
            $employee->user_id = $user->id; // set explicitly, never mass-assigned
            $employee->save();
        });

        return redirect()->route('employees.index')
            ->with('success', 'Employee created. They can now log in with the password you set.');
    }

    public function show(Employee $employee): View
    {
        $employee->load('department', 'user');

        $recentAttendance = $employee->attendance()
            ->latest('attendance_date')
            ->limit(10)
            ->get();

        return view('employees.show', compact('employee', 'recentAttendance'));
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->safe()->except('password');
        $password = $request->validated('password');

        DB::transaction(function () use ($data, $password, $employee) {
            $employee->update($data);

            $user = $employee->user;

            if ($user === null) {
                // Older employee without a login: create one if a password was supplied.
                if ($password) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => $password,
                        'role' => User::ROLE_EMPLOYEE,
                        'is_active' => $employee->isActive(),
                    ]);
                    $employee->user()->associate($user)->save();
                }

                return;
            }

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->is_active = $employee->isActive();

            if ($password) {
                $user->password = $password;
            }

            $user->save();
        });

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee details updated.');
    }

    /**
     * Activate or deactivate an employee. A deactivated employee cannot log in,
     * and the linked login account is switched off in the same step.
     */
    public function toggleStatus(Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        DB::transaction(function () use ($employee) {
            $newStatus = $employee->isActive()
                ? Employee::STATUS_INACTIVE
                : Employee::STATUS_ACTIVE;

            $employee->update(['status' => $newStatus]);

            $employee->user?->update(['is_active' => $newStatus === Employee::STATUS_ACTIVE]);
        });

        $message = $employee->fresh()->isActive()
            ? 'Employee activated.'
            : 'Employee deactivated. They can no longer log in.';

        return back()->with('success', $message);
    }
}
