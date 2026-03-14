<?php

namespace App\Listeners;

use App\Events\VoiceCreditExhaustedEvent;
use App\Mail\VoiceCreditExhaustedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVoiceCreditExhaustedNotification implements ShouldQueue
{
    public function handle(VoiceCreditExhaustedEvent $event): void
    {
        Mail::to($event->business->email)->send(
            new VoiceCreditExhaustedMail($event->business)
        );
    }
}
