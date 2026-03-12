<?php

namespace App\Services;

use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Security\RequestValidator;

class TwilioSmsService implements SmsServiceInterface
{
    public function send(string $to, string $body): array
    {
        $client = new Client(
            (string) config('services.twilio.sid'),
            (string) config('services.twilio.token'),
        );

        $message = $client->messages->create($to, [
            'from' => (string) config('services.twilio.from'),
            'body' => $body,
        ]);

        return [
            'sid' => $message->sid,
            'status' => $message->status ?? 'sent',
        ];
    }

    public function validateWebhookSignature(Request $request): bool
    {
        $validator = new RequestValidator((string) config('services.twilio.token'));

        return $validator->validate(
            (string) $request->header('X-Twilio-Signature'),
            $request->fullUrl(),
            $request->post(),
        );
    }
}
