<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'seller')->with('labels');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('surname', 'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%")
                  ->orWhere('company_name', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        return response()->json(
            UserResource::collection($query->latest()->paginate(25))
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $token = Str::random(64);

        $user = User::create([
            ...$request->validated(),
            'role'             => 'seller',
            'password'         => Hash::make(Str::random(16)),
            'activation_token' => $token,
            'is_active'        => false,
        ]);

        // TODO: Send activation email via queue
        // SendActivationEmail::dispatch($user);

        return response()->json(new UserResource($user), 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['labels.releases', 'reports', 'paymentRequests'])
            ->findOrFail($id);

        return response()->json(new UserResource($user));
    }

    public function update(StoreCustomerRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());

        return response()->json(new UserResource($user->fresh('labels')));
    }

    public function activate(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true, 'is_blocked' => false]);

        return response()->json(['message' => 'Customer activated.']);
    }

    public function deactivate(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Customer deactivated.']);
    }

    public function block(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_blocked' => true]);

        return response()->json(['message' => 'Customer blocked.']);
    }

    public function resetPassword(int $id): JsonResponse
    {
        $user  = User::findOrFail($id);
        $token = Str::random(64);

        $user->update(['activation_token' => $token]);

        // TODO: Send password reset email
        // SendPasswordResetEmail::dispatch($user, $token);

        return response()->json(['message' => 'Password reset email sent.']);
    }

    public function toggleFeatured(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['featured' => ! $user->featured]);

        return response()->json(['featured' => $user->featured]);
    }
}
