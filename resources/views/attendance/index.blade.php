@extends('layouts.app')

@section('title', 'Attendance')
@section('heading', 'Attendance')

@section('content')
<h1 class="h4 mb-3">Attendance</h1>

{{-- Check-in / check-out (for any user linked to an employee profile) --}}
@if ($linkedEmployee)
    <section class="panel mb-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
                <div class="small text-muted">Today, {{ now()->format('d M Y') }}</div>
                @if ($todayRecord === null)
                    <div class="fw-semibold">You have not checked in yet.</div>
                @elseif ($todayRecord->check_out_at === null)
                    <div class="fw-semibold">Checked in at {{ $todayRecord->check_in_at->format('h:i A') }}</div>
                @else
                    <div class="fw-semibold">
                        Checked in {{ $todayRecord->check_in_at->format('h:i A') }},
                        out {{ $todayRecord->check_out_at->format('h:i A') }}
                        ({{ $todayRecord->workingHoursLabel() }})
                    </div>
                @endif
            </div>
            <div class="col-12 col-md-6 d-flex flex-column flex-sm-row gap-2 justify-content-md-end">
                <form method="POST" action="{{ route('attendance.check-in') }}" data-once>
                    @csrf
                    <button type="submit" class="btn btn-success w-100" @disabled($todayRecord !== null)>Check in</button>
                </form>
                <form method="POST" action="{{ route('attendance.check-out') }}" data-once>
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100"
                            @disabled($todayRecord === null || $todayRecord->check_out_at !== null)>Check out</button>
                </form>
            </div>
        </div>
    </section>
@else
    <div class="alert alert-info small">
        Your login is not linked to an employee profile, so you cannot check in. Ask the administrator.
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('attendance.index') }}" class="panel mb-3">
    <div class="row g-2 align-items-end">
        @if (auth()->user()->isAdmin())
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="employee_id">Employee</label>
                <select id="employee_id" name="employee_id" class="form-select">
                    <option value="">All employees</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? null) == $employee->id)>
                            {{ $employee->name }} ({{ $employee->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="date">Single date</label>
            <input type="date" id="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="from">From</label>
            <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="to">To</label>
            <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
        </div>
        <div class="col-6 col-md-2 d-grid gap-2 d-md-flex">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
    @error('to')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</form>

{{-- Records --}}
<section class="panel p-0 mb-4">
    <div class="table-wrap">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    @if (auth()->user()->isAdmin())<th>Employee</th>@endif
                    <th>Date</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Working hours</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        @if (auth()->user()->isAdmin())
                            <td>
                                {{ $record->employee->name }}
                                <div class="small text-muted">{{ $record->employee->employee_code }}</div>
                            </td>
                        @endif
                        <td>{{ $record->attendance_date->format('d M Y') }}</td>
                        <td>{{ $record->check_in_at->format('h:i A') }}</td>
                        <td>{{ $record->check_out_at?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $record->workingHoursLabel() ?? 'In progress' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="text-center text-muted py-4">
                            No attendance records match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="mb-4">
    {{ $records->links() }}
</div>

{{-- Admin only: who has not checked in for the chosen day --}}
@if (auth()->user()->isAdmin() && empty($filters['employee_id']))
    <section class="panel p-0">
        <div class="p-3 border-bottom">
            <h2 class="panel-title mb-0">Absent on {{ \Illuminate\Support\Carbon::parse($absentDate)->format('d M Y') }}</h2>
            <div class="small text-muted">Active employees with no check-in on this date.</div>
        </div>
        <div class="table-wrap">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Department</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($absentEmployees as $employee)
                        <tr>
                            <td>{{ $employee->employee_code }}</td>
                            <td>{{ $employee->name }}</td>
                            <td class="d-none d-md-table-cell">{{ $employee->department->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Everyone has checked in.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
