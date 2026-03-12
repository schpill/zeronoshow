<?php

namespace App\Services\Contracts;

use Illuminate\Http\Request;

interface SmsServiceInterface
{
    /**
     * @return array{sid:?string,status:string}
     */
    public function send(string $to, string $body): array;

    public function validateWebhookSignature(Request $request): bool;
}
