<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ]);

        $customer = Customer::query()->where('phone', $validated['phone'])->first();

        return response()->json([
            'found' => (bool) $customer,
            'reliability_score' => $customer?->reliability_score,
            'score_tier' => $customer?->reliability_score === null ? null : $customer->getScoreTier(),
        ]);
    }
}
