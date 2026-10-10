@extends('layouts.app')

@section('title', 'Add employee')
@section('heading', 'Add employee')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Add employee</h1>
    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ route('employees.store') }}" class="panel" data-once>
    @csrf
    @include('employees._form', ['employee' => null])

    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Save employee</button>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
