<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'id' => $customer->id,
            'phone' => $customer->phone,
            'reliability_score' => $customer->reliability_score,
            'score_tier' => $customer->getScoreTier(),
            'reservations_count' => $customer->reservations_count,
            'shows_count' => $customer->shows_count,
            'no_shows_count' => $customer->no_shows_count,
            'opted_out' => $customer->opted_out,
            'notes' => $customer->notes,
            'is_vip' => $customer->is_vip,
            'is_blacklisted' => $customer->is_blacklisted,
            'birthday_month' => $customer->birthday_month,
            'birthday_day' => $customer->birthday_day,
            'preferred_table_notes' => $customer->preferred_table_notes,
        ];
    }
}
