<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * POST /api/v1/auth/register
     * 
     * Admin-only: Create a new user account.
     * Regular users cannot self-register — this is an internal system.
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'staff',
                'department' => $request->department,
                'phone' => $request->phone,
                'status' => 'active',
                'force_password_change' => true, // New users must change password on first login
            ]);

            AuditLog::record(
                auth()->id(),
                'user_created',
                'user',
                $user->id,
                null,
                ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            );

            return $this->success([
                'user' => $user->toAuthArray(),
            ], 'User created successfully', 201);
        });
    }
}
