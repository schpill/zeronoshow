<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetWhatsAppCapRequest;
use App\Http\Requests\TopUpWhatsAppRequest;
use App\Http\Resources\LeoWhatsAppCreditResource;
use App\Models\Business;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Leo', description: 'Leo WhatsApp credit endpoints')]
class LeoWhatsAppCreditController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    #[OA\Get(
        path: '/api/v1/leo/whatsapp/credits',
        tags: ['Leo'],
        summary: 'Get WhatsApp credit status',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'WhatsApp credit status')],
    )]
    public function status(Request $request): LeoWhatsAppCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new LeoWhatsAppCreditResource($business->load('leoChannel'));
    }

    #[OA\Post(
        path: '/api/v1/leo/whatsapp/credits/topup',
        tags: ['Leo'],
        summary: 'Top up WhatsApp credits',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Checkout URL returned')],
    )]
    public function topup(TopUpWhatsAppRequest $request): JsonResponse
    {
        /** @var Business $business */
        $business = $request->user();

        $session = $this->stripe->createWhatsAppCreditCheckoutSession(
            $business,
            $request->integer('amount_cents')
        );

        return response()->json([
            'checkout_url' => $session['url'],
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/leo/whatsapp/credits/cap',
        tags: ['Leo'],
        summary: 'Set WhatsApp monthly cap',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Cap updated')],
    )]
    public function setCap(SetWhatsAppCapRequest $request): LeoWhatsAppCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        $business->update([
            'whatsapp_monthly_cap_cents' => $request->integer('monthly_cap_cents'),
            'whatsapp_auto_renew' => $request->boolean('auto_renew'),
        ]);

        return new LeoWhatsAppCreditResource($business->load('leoChannel'));
    }
}
