<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetVoiceCapRequest;
use App\Http\Requests\TopUpVoiceRequest;
use App\Http\Resources\VoiceCreditResource;
use App\Models\Business;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceCreditController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    public function status(Request $request): VoiceCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new VoiceCreditResource($business->load('leoChannel'));
    }

    public function topup(TopUpVoiceRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $request->user();

        $session = $this->stripe->createVoiceCreditCheckoutSession(
            $business,
            $request->integer('amount_cents')
        );

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    public function setCap(SetVoiceCapRequest $request): VoiceCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        $business->update([
            'voice_monthly_cap_cents' => $request->integer('monthly_cap_cents'),
            'voice_auto_renew' => $request->boolean('auto_renew'),
        ]);

        return new VoiceCreditResource($business->load('leoChannel'));
    }
}
