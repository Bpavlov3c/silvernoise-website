<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Account has been blocked.'], 403);
        }

        if ($user->contract_terminated_at) {
            return response()->json(['message' => 'Contract has been terminated.'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('labels')));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'surname'      => 'sometimes|string|max:100',
            'company_name' => 'sometimes|nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return response()->json(new UserResource($request->user()->fresh()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $request->user()->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Always return success to prevent email enumeration
        return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function activate(string $token): JsonResponse
    {
        $user = User::where('activation_token', $token)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired activation link.'], 404);
        }

        $user->update([
            'is_active'        => true,
            'activation_token' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Account activated successfully.']);
    }
}
