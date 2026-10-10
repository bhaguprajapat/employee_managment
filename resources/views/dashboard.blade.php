@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Employees</div>
            <div class="stat-value">{{ $totalEmployees }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Active Employees</div>
            <div class="stat-value text-success">{{ $activeEmployees }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Today's Present</div>
            <div class="stat-value text-primary">{{ $presentToday }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Today's Absent</div>
            <div class="stat-value text-danger">{{ $absentToday }}</div>
        </div>
    </div>
</div>

<section class="panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="panel-title mb-0">Recent Attendance</h2>
        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
    </div>

    <div class="table-wrap">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Working hours</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentAttendance as $record)
                    <tr>
                        <td>
                            {{ $record->employee->name }}
                            <div class="small text-muted">{{ $record->employee->employee_code }}</div>
                        </td>
                        <td>{{ $record->attendance_date->format('d M Y') }}</td>
                        <td>{{ $record->check_in_at->format('h:i A') }}</td>
                        <td>{{ $record->check_out_at?->format('h:i A') ?? '—' }}</td>
                        <td>{{ $record->workingHoursLabel() ?? 'In progress' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No attendance recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
