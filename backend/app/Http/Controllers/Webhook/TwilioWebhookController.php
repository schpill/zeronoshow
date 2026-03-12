<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TwilioWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        Log::info('Twilio webhook payload', $request->all());

        return response('', 200);
    }
}
