@extends('layouts.app')

@section('title', $employee->name)
@section('heading', 'Employee details')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">{{ $employee->name }}</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="POST" action="{{ route('employees.toggle-status', $employee) }}">
            @csrf
            @method('PATCH')
            @if ($employee->isActive())
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Deactivate this employee? They will no longer be able to log in.">Deactivate</button>
            @else
                <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
            @endif
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <section class="panel h-100">
            <h2 class="panel-title">Profile</h2>
            <dl class="row mb-0 small">
                <dt class="col-5 text-muted">Employee code</dt>
                <dd class="col-7">{{ $employee->employee_code }}</dd>

                <dt class="col-5 text-muted">Email</dt>
                <dd class="col-7 text-break">{{ $employee->email }}</dd>

                <dt class="col-5 text-muted">Mobile</dt>
                <dd class="col-7">{{ $employee->mobile_number }}</dd>

                <dt class="col-5 text-muted">Department</dt>
                <dd class="col-7">{{ $employee->department->name }}</dd>

                <dt class="col-5 text-muted">Designation</dt>
                <dd class="col-7">{{ $employee->designation }}</dd>

                <dt class="col-5 text-muted">Joining date</dt>
                <dd class="col-7">{{ $employee->joining_date->format('d M Y') }}</dd>

                <dt class="col-5 text-muted">Status</dt>
                <dd class="col-7">
                    <span class="badge rounded-pill badge-status-{{ $employee->status }}">{{ ucfirst($employee->status) }}</span>
                </dd>

                <dt class="col-5 text-muted">Login</dt>
                <dd class="col-7">{{ $employee->user ? 'Linked' : 'Not linked' }}</dd>
            </dl>
        </section>
    </div>

    <div class="col-12 col-lg-7">
        <section class="panel h-100">
            <h2 class="panel-title">Recent attendance</h2>
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendance as $record)
                            <tr>
                                <td>{{ $record->attendance_date->format('d M Y') }}</td>
                                <td>{{ $record->check_in_at->format('h:i A') }}</td>
                                <td>{{ $record->check_out_at?->format('h:i A') ?? '—' }}</td>
                                <td>{{ $record->workingHoursLabel() ?? 'In progress' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No attendance yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
