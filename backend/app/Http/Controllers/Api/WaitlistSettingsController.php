<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WaitlistSettingsRequest;
use App\Services\WaitlistPublicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitlistSettingsController extends Controller
{
    public function show(Request $request, WaitlistPublicLinkService $publicLinkService): JsonResponse
    {
        $business = $request->user();

        return response()->json([
            'waitlist_enabled' => $business->waitlist_enabled,
            'waitlist_notification_window_minutes' => $business->waitlist_notification_window_minutes,
            'waitlist_public_token' => $business->waitlist_public_token,
            'public_registration_url' => $publicLinkService->getPublicUrl($business),
        ]);
    }

    public function update(WaitlistSettingsRequest $request): JsonResponse
    {
        $business = $request->user();

        $business->update($request->validated());

        return response()->json([
            'message' => 'Paramètres mis à jour.',
            'settings' => [
                'waitlist_enabled' => $business->waitlist_enabled,
                'waitlist_notification_window_minutes' => $business->waitlist_notification_window_minutes,
            ],
        ]);
    }

    public function regenerateLink(Request $request, WaitlistPublicLinkService $publicLinkService): JsonResponse
    {
        $business = $request->user();

        $token = $publicLinkService->generateToken($business);

        return response()->json([
            'message' => 'Lien public régénéré.',
            'waitlist_public_token' => $token,
            'public_registration_url' => $publicLinkService->getPublicUrl($business),
        ]);
    }
}
