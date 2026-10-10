@extends('layouts.app')

@section('title', 'Employees')
@section('heading', 'Employees')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Employees</h1>
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">+ Add employee</a>
</div>

<form method="GET" action="{{ route('employees.index') }}" class="panel mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label small mb-1" for="q">Search</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Name, code, email or designation">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="department_id">Department</label>
            <select id="department_id" name="department_id" class="form-select">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
        </div>
    </div>
</form>

<div class="panel p-0">
    <div class="table-wrap">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th class="d-none d-md-table-cell">Email</th>
                    <th class="d-none d-lg-table-cell">Department</th>
                    <th class="d-none d-md-table-cell">Designation</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_code }}</td>
                        <td>{{ $employee->name }}</td>
                        <td class="d-none d-md-table-cell">{{ $employee->email }}</td>
                        <td class="d-none d-lg-table-cell">{{ $employee->department->name }}</td>
                        <td class="d-none d-md-table-cell">{{ $employee->designation }}</td>
                        <td>
                            <span class="badge rounded-pill badge-status-{{ $employee->status }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No employees match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $employees->links() }}
</div>
@endsection
