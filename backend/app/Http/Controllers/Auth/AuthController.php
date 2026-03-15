<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Business authentication endpoints')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        tags: ['Auth'],
        summary: 'Register a business account',
        responses: [new OA\Response(response: 201, description: 'Registered')],
    )]
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
            'public_token' => (string) Str::uuid(),
        ]);

        return response()->json([
            'token' => $business->createToken('web')->plainTextToken,
            'business' => $this->businessPayload($business),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        tags: ['Auth'],
        summary: 'Login a business account',
        responses: [
            new OA\Response(response: 200, description: 'Logged in'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ],
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/logout',
        tags: ['Auth'],
        summary: 'Logout current business token',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 204, description: 'Logged out')],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    private function businessPayload(Business $business): array
    {
        return (new BusinessResource($business))->toArray(request());
    }
}
