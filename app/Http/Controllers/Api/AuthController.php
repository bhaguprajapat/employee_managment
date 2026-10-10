<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::with('employee')->where('email', $data['email'])->first();

        // Same message for "no such user" and "wrong password" so accounts cannot be probed.
        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('Invalid email or password.', 401);
        }

        if (! $user->canSignIn()) {
            return ApiResponse::error('This account is deactivated.', 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'api-client')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_id' => $user->employee?->id,
            ],
        ], 'Login successful.');
    }
}
