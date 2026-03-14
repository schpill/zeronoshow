<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\VoiceCallStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyWaitlistJob;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VoiceGatherController extends Controller
{
    public function gather(Request $request, VoiceCallLog $voiceCallLog): Response
    {
        $voiceCallLog->loadMissing('reservation.business');
        $digit = $request->string('Digits')->toString();

        /** @var Reservation $reservation */
        $reservation = $voiceCallLog->reservation;

        if ($digit === '1') {
            $voiceCallLog->update([
                'status' => VoiceCallStatusEnum::Confirmed,
                'dtmf_response' => '1',
            ]);
            $reservation->update([
                'status' => 'confirmed',
                'status_changed_at' => now(),
            ]);

            return $this->xml('Votre réservation est confirmée. Merci.');
        }

        if ($digit === '2') {
            $voiceCallLog->update([
                'status' => VoiceCallStatusEnum::Declined,
                'dtmf_response' => '2',
            ]);
            $reservation->update([
                'status' => 'cancelled_by_client',
                'status_changed_at' => now(),
            ]);

            if ($reservation->business->waitlist_enabled) {
                NotifyWaitlistJob::dispatch(
                    $reservation->business_id,
                    $reservation->scheduled_at->format('Y-m-d'),
                    $reservation->scheduled_at->format('H:i:00')
                );
            }

            return $this->xml('Votre réservation a été annulée. Au revoir.');
        }

        return $this->xml('Touche non reconnue. Merci de réessayer et de saisir 1 pour confirmer ou 2 pour annuler.');
    }

    private function xml(string $message): Response
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say voice="Polly.Léa" language="fr-FR">{$message}</Say>
</Response>
XML;

        return response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }
}
