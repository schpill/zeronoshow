<?php

namespace App\Listeners;

use App\Events\LeoWhatsAppLowBalanceEvent;
use App\Mail\WhatsAppLowBalanceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendLowBalanceNotification implements ShouldQueue
{
    public function handle(LeoWhatsAppLowBalanceEvent $event): void
    {
        Mail::to($event->business->email)->send(
            new WhatsAppLowBalanceMail($event->business, $event->balanceCents)
        );
    }
}
