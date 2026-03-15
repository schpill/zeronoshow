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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Voice', description: 'Voice credit endpoints')]
class VoiceCreditController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    #[OA\Get(
        path: '/api/v1/voice/credits',
        tags: ['Voice'],
        summary: 'Get voice credit status',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Voice credit status')],
    )]
    public function status(Request $request): VoiceCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new VoiceCreditResource($business->load('leoChannel'));
    }

    #[OA\Post(
        path: '/api/v1/voice/credits/topup',
        tags: ['Voice'],
        summary: 'Top up voice credits',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Checkout URL returned')],
    )]
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

    #[OA\Patch(
        path: '/api/v1/voice/credits/cap',
        tags: ['Voice'],
        summary: 'Set voice monthly cap',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Cap updated')],
    )]
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
