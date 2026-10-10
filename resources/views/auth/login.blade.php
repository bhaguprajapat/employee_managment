@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="text-center mb-4">
            <span class="brand-mark d-inline-grid mb-2">EM</span>
            <h1 class="h4 mb-1">Sign in</h1>
            <p class="text-muted small mb-0">{{ config('app.name') }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success small" role="alert">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       autocomplete="username" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="current-password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Sign in</button>
        </form>
    </div>
</div>
@endsection
