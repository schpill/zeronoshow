<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $business = Business::query()->create([
            'name' => $request->string('business_name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'phone' => $request->string('phone')->toString(),
            'timezone' => 'Europe/Paris',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        return response()->json([
            'token' => $business->createToken('web')->plainTextToken,
            'business' => $this->businessPayload($business),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var Business $business */
        $business = Auth::user();

        return response()->json([
            'token' => $business->createToken('web')->plainTextToken,
            'business' => $this->businessPayload($business),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    private function businessPayload(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'email' => $business->email,
            'phone' => $business->phone,
            'trial_ends_at' => optional($business->trial_ends_at)->toIso8601String(),
            'subscription_status' => $business->subscription_status,
            'leo_addon_active' => (bool) $business->leo_addon_active,
        ];
    }
}
