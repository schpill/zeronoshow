<?php

namespace App\Listeners;

use App\Events\VoiceLowBalanceEvent;
use App\Mail\VoiceLowBalanceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVoiceLowBalanceNotification implements ShouldQueue
{
    public function handle(VoiceLowBalanceEvent $event): void
    {
        Mail::to($event->business->email)->send(
            new VoiceLowBalanceMail($event->business, $event->balanceCents)
        );
    }
}
