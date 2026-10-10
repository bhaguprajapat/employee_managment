{{-- Shared fields for create and edit. $employee is set on edit, null on create. --}}
@php
    $value = fn (string $field) => old($field, $employee?->{$field});
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="employee_code">Employee code</label>
        <input id="employee_code" name="employee_code" value="{{ $value('employee_code') }}" maxlength="20"
               class="form-control @error('employee_code') is-invalid @enderror" required>
        @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="name">Full name</label>
        <input id="name" name="name" value="{{ $value('name') }}" maxlength="120"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="email">Email (also the login email)</label>
        <input id="email" type="email" name="email" value="{{ $value('email') }}" maxlength="191"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="mobile_number">Mobile number</label>
        <input id="mobile_number" name="mobile_number" value="{{ $value('mobile_number') }}" maxlength="20"
               inputmode="tel" class="form-control @error('mobile_number') is-invalid @enderror" required>
        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="department_id">Department</label>
        <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
            <option value="">Select a department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $value('department_id') === $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="designation">Designation</label>
        <input id="designation" name="designation" value="{{ $value('designation') }}" maxlength="100"
               class="form-control @error('designation') is-invalid @enderror" required>
        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="joining_date">Joining date</label>
        <input id="joining_date" type="date" name="joining_date" value="{{ $value('joining_date') ? \Illuminate\Support\Carbon::parse($value('joining_date'))->format('Y-m-d') : '' }}"
               class="form-control @error('joining_date') is-invalid @enderror" required>
        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="active" @selected($value('status') === 'active')>Active</option>
            <option value="inactive" @selected($value('status') === 'inactive')>Inactive</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><hr class="my-1"><h2 class="h6 mb-0">Login password</h2>
        <p class="small text-muted mb-2">
            @if ($employee)
                Leave blank to keep the current password.
            @else
                The employee signs in with this email and password.
            @endif
        </p>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="password">Password (min 8 characters)</label>
        <input id="password" type="password" name="password" autocomplete="new-password"
               class="form-control @error('password') is-invalid @enderror" {{ $employee ? '' : 'required' }}>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
               class="form-control" {{ $employee ? '' : 'required' }}>
    </div>
</div>
