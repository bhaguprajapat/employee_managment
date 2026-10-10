<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

// Public: returns a Sanctum Bearer token
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Protected: send "Authorization: Bearer {token}"
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index']);          // admin only
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']); // admin, or the employee themself

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance', [AttendanceController::class, 'index']);       // employee: own rows; admin: all
});
