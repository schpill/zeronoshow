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

class LeoWhatsAppCreditController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
    ) {}

    public function status(Request $request): LeoWhatsAppCreditResource
    {
        /** @var Business $business */
        $business = $request->user();

        return new LeoWhatsAppCreditResource($business->load('leoChannel'));
    }

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
