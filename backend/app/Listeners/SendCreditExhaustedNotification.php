<?php

namespace App\Listeners;

use App\Events\LeoWhatsAppCreditExhaustedEvent;
use App\Mail\WhatsAppCreditExhaustedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendCreditExhaustedNotification implements ShouldQueue
{
    public function handle(LeoWhatsAppCreditExhaustedEvent $event): void
    {
        Mail::to($event->business->email)->send(
            new WhatsAppCreditExhaustedMail($event->business)
        );
    }
}
