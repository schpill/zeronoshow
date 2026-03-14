<?php

namespace App\Leo\Tools;

use App\Events\LeoWhatsAppCreditExhaustedEvent;
use App\Events\LeoWhatsAppLowBalanceEvent;
use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Support\Facades\DB;

class LeoWhatsAppCreditService
{
    public function getBalance(Business $business): int
    {
        return $business->whatsapp_credit_cents;
    }

    public function hasSufficientCredit(Business $business, int $requiredCents): bool
    {
        return $business->whatsapp_credit_cents >= $requiredCents;
    }

    public function deduct(Business $business, int $costCents): void
    {
        DB::transaction(function () use ($business, $costCents) {
            $business->lockForUpdate();
            $business->whatsapp_credit_cents = max(0, $business->whatsapp_credit_cents - $costCents);
            $business->save();

            if ($business->whatsapp_credit_cents <= 0) {
                $this->suspendWhatsAppChannel($business);

                /** @var LeoChannel|null $channel */
                $channel = $business->leoChannel()->where('channel', 'whatsapp')->first();
                if ($channel) {
                    LeoWhatsAppCreditExhaustedEvent::dispatch($business, $channel);
                }
            } elseif ($business->whatsapp_credit_cents < config('leo.whatsapp.low_balance_threshold_cents')) {
                LeoWhatsAppLowBalanceEvent::dispatch($business, $business->whatsapp_credit_cents);
            }
        });
    }

    public function topUp(Business $business, int $amountCents): void
    {
        DB::transaction(function () use ($business, $amountCents) {
            $business->lockForUpdate();
            $business->whatsapp_credit_cents += $amountCents;
            $business->save();

            /** @var LeoChannel|null $channel */
            $channel = $business->leoChannel()->where('channel', 'whatsapp')->first();
            if ($channel && ! $channel->is_active) {
                $channel->update(['is_active' => true]);
            }
        });
    }

    public function suspendWhatsAppChannel(Business $business): void
    {
        $business->leoChannel()->where('channel', 'whatsapp')->update(['is_active' => false]);
    }

    public function getConversationCost(string $type): int
    {
        return match ($type) {
            'service' => config('leo.whatsapp.cost_service_cents'),
            'utility' => config('leo.whatsapp.cost_utility_cents'),
            default => 0,
        };
    }
}
