<?php

namespace App\Events;

use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeoWhatsAppCreditExhaustedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly LeoChannel $channel,
    ) {}
}
