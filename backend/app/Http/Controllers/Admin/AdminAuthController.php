<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(
    name: 'Admin Auth',
    description: 'Authentication endpoints for the ZeroNoShow operator backoffice',
)]
class AdminAuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/admin/login',
        tags: ['Admin Auth'],
        summary: 'Authenticate an admin account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 429, description: 'Admin lockout active'),
        ],
    )]
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $email = mb_strtolower($request->string('email')->toString());
        $lockoutKey = "admin:lockout:{$email}";
        $attemptsKey = "admin:attempts:{$email}";

        if ($this->isLockedOut($lockoutKey)) {
            return response()->json([
                'message' => 'Trop de tentatives, veuillez reessayer dans 15 minutes.',
            ], 429);
        }

        $credentials = [
            'email' => $email,
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::guard('admin')->attempt($credentials)) {
            $this->recordFailedAttempt($attemptsKey, $lockoutKey);

            return response()->json([
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $this->clearThrottleKeys($attemptsKey, $lockoutKey);

        $token = $admin->createToken('admin', ['admin'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/logout',
        tags: ['Admin Auth'],
        summary: 'Revoke the current admin token',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Logged out'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    private function isLockedOut(string $lockoutKey): bool
    {
        try {
            return (bool) Redis::exists($lockoutKey);
        } catch (Throwable) {
            return false;
        }
    }

    private function recordFailedAttempt(string $attemptsKey, string $lockoutKey): void
    {
        try {
            $attempts = (int) Redis::incr($attemptsKey);

            if ($attempts === 1) {
                Redis::expire($attemptsKey, 900);
            }

            if ($attempts >= 5) {
                Redis::setex($lockoutKey, 900, 1);
                Redis::del($attemptsKey);
            }
        } catch (Throwable) {
            // Redis-backed lockout is best effort; auth still returns 401 if Redis is unavailable.
        }
    }

    private function clearThrottleKeys(string $attemptsKey, string $lockoutKey): void
    {
        try {
            Redis::del($attemptsKey);
            Redis::del($lockoutKey);
        } catch (Throwable) {
            // Ignore Redis cleanup failures; successful auth should not be blocked.
        }
    }
}
