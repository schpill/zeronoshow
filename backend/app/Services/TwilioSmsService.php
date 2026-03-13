<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryException;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Http\Request;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\Security\RequestValidator;

class TwilioSmsService implements SmsServiceInterface
{
    public function __construct(private readonly ?Client $client = null) {}

    public function send(SmsLog $smsLog): SmsLog
    {
        $client = $this->client ?? new Client(
            (string) config('services.twilio.sid'),
            (string) config('services.twilio.token'),
        );

        try {
            $message = $client->messages->create($smsLog->phone, [
                'from' => (string) config('services.twilio.from'),
                'body' => $smsLog->body,
            ]);
        } catch (TwilioException $exception) {
            throw new SmsDeliveryException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $smsLog->update([
            'twilio_sid' => $message->sid,
            'status' => $message->status ?? 'sent',
            'sent_at' => now(),
        ]);

        return $smsLog->fresh();
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
