<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Reservation;
use App\Models\VoiceCallLog;
use Symfony\Component\HttpFoundation\Response;

class VoiceTwimlController extends Controller
{
    public function twiml(VoiceCallLog $voiceCallLog): Response
    {
        $voiceCallLog->loadMissing(['reservation.business']);

        /** @var Reservation $reservation */
        $reservation = $voiceCallLog->reservation;
        /** @var Business $business */
        $business = $reservation->business;
        $when = $reservation->scheduled_at->timezone($business->timezone);
        $gatherUrl = route('voice.gather', ['voiceCallLog' => $voiceCallLog->id], false);
        $fullGatherUrl = rtrim((string) config('app.url'), '/').$gatherUrl;

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Gather numDigits="1" action="{$fullGatherUrl}" method="POST" timeout="10">
    <Say voice="Polly.Léa" language="fr-FR">Bonjour {$reservation->customer_name}, vous avez une réservation chez {$business->name} le {$when->format('d/m/Y')} à {$when->format('H:i')} pour {$reservation->guests} personne(s). Appuyez sur 1 pour confirmer, sur 2 pour annuler.</Say>
  </Gather>
  <Say voice="Polly.Léa" language="fr-FR">Nous n'avons pas reçu votre réponse. Merci.</Say>
</Response>
XML;

        return response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }
}
