<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        return [
            'id' => $reservation->id,
            'customer_name' => $reservation->customer_name,
            'scheduled_at' => optional($reservation->scheduled_at)->toIso8601String(),
            'guests' => $reservation->guests,
            'notes' => $reservation->notes,
            'status' => $reservation->status,
            'phone_verified' => $reservation->phone_verified,
            'reminder_2h_sent' => $reservation->reminder_2h_sent,
            'reminder_30m_sent' => $reservation->reminder_30m_sent,
            'confirmation_token' => $this->whenNotNull($reservation->confirmation_token),
            'token_expires_at' => optional($reservation->token_expires_at)->toIso8601String(),
            'status_changed_at' => optional($reservation->status_changed_at)->toIso8601String(),
            'created_at' => optional($reservation->created_at)->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $reservation->customer->id,
                'phone' => $reservation->customer->phone,
                'reliability_score' => $reservation->customer->reliability_score,
                'score_tier' => $reservation->customer->getScoreTier(),
                'reservations_count' => $reservation->customer->reservations_count,
                'shows_count' => $reservation->customer->shows_count,
                'no_shows_count' => $reservation->customer->no_shows_count,
                'opted_out' => $reservation->customer->opted_out,
            ]),
            'sms_count' => $this->whenCounted('smsLogs', $reservation->sms_logs_count),
        ];
    }
}
