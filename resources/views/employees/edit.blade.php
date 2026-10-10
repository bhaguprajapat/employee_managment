@extends('layouts.app')

@section('title', 'Edit employee')
@section('heading', 'Edit employee')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Edit {{ $employee->name }}</h1>
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ route('employees.update', $employee) }}" class="panel" data-once>
    @csrf
    @method('PUT')
    @include('employees._form', ['employee' => $employee])

    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Update employee</button>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
