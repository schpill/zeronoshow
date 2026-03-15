<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function completeOnboarding(Request $request): JsonResponse
    {
        $business = $request->user();

        if ($business->onboarding_completed_at === null) {
            $business->update([
                'onboarding_completed_at' => now(),
            ]);
        }

        return response()->json(new BusinessResource($business->fresh()));
    }
}
