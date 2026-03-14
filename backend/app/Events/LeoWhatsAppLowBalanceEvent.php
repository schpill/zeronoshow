<?php

namespace App\Events;

use App\Models\Business;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeoWhatsAppLowBalanceEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly int $balanceCents,
    ) {}
}
