<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->whereHas('reservations', fn ($reservationQuery) => $reservationQuery->where('business_id', $request->user()->id))
            ->orderByDesc('reservations_count');

        if ((bool) $request->input('filter.is_vip')) {
            $query->vip();
        }

        if ((bool) $request->input('filter.is_blacklisted')) {
            $query->blacklisted();
        }

        if ($request->filled('filter.birthday_month')) {
            $query->where('birthday_month', (int) $request->input('filter.birthday_month'));
        }

        return CustomerResource::collection($query->distinct()->get());
    }

    public function lookup(Request $request): JsonResponse
    {
        $rawPhone = (string) $request->input('phone');
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $normalizedPhone = str_starts_with(trim($rawPhone), '+')
            ? '+'.$digits
            : ($digits !== '' ? '+'.$digits : $rawPhone);

        $request->merge(['phone' => $normalizedPhone]);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ]);

        $customer = Customer::query()->where('phone', $validated['phone'])->first();

        return response()->json([
            'found' => (bool) $customer,
            'reliability_score' => $customer?->reliability_score,
            'score_tier' => $customer?->getScoreTier(),
            'opted_out' => $customer?->opted_out,
            'is_blacklisted' => $customer ? $customer->is_blacklisted : false,
        ]);
    }
}
