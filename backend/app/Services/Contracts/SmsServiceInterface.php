<?php

namespace App\Services\Contracts;

use App\Models\SmsLog;
use Illuminate\Http\Request;

interface SmsServiceInterface
{
    public function send(SmsLog $smsLog): SmsLog;

    public function validateWebhookSignature(Request $request): bool;
}
