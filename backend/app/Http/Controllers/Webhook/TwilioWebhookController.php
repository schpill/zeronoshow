<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwilioWebhookController extends Controller
{
    public function handle(Request $request, SmsServiceInterface $sms): Response
    {
        if (! $sms->validateWebhookSignature($request)) {
            return response('', 403);
        }

        $from = $request->string('From')->toString();
        $body = strtoupper(trim($request->string('Body')->toString()));

        if ($from !== '' && in_array($body, ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'], true)) {
            Customer::query()
                ->where('phone', $from)
                ->update([
                    'opted_out' => true,
                    'opted_out_at' => now(),
                ]);
        }

        $sid = $request->string('MessageSid')->toString();

        if ($sid !== '') {
            $smsLog = SmsLog::query()->where('twilio_sid', $sid)->first();

            if ($smsLog) {
                $status = $request->string('MessageStatus')->toString() ?: $request->string('SmsStatus')->toString();
                $price = $request->input('Price');

                $smsLog->update([
                    'status' => $status !== '' ? $status : $smsLog->status,
                    'cost_eur' => is_numeric($price) ? abs((float) $price) : $smsLog->cost_eur,
                    'delivered_at' => $status === 'delivered' ? now() : $smsLog->delivered_at,
                    'error_message' => $request->input('ErrorMessage') ?: $smsLog->error_message,
                ]);
            }
        }

        return response('', 200);
    }
}
