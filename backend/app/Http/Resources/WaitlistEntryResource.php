<?php

namespace App\Http\Resources;

use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WaitlistEntry
 */
class WaitlistEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'slot_date' => $this->slot_date->format('Y-m-d'),
            'slot_time' => substr($this->slot_time, 0, 5), // H:i
            'client_name' => $this->client_name,
            'client_phone' => $this->maskPhone($this->client_phone),
            'party_size' => $this->party_size,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'channel' => $this->channel?->value,
            'priority_order' => $this->priority_order,
            'notified_at' => $this->notified_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function maskPhone(string $phone): string
    {
        // Keep + and last 4 digits, replace rest with *
        $length = strlen($phone);
        if ($length <= 5) {
            return $phone;
        }

        return substr($phone, 0, 1).str_repeat('*', $length - 5).substr($phone, -4);
    }
}
