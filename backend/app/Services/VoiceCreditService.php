<?php

namespace App\Services;

use App\Events\VoiceCreditExhaustedEvent;
use App\Events\VoiceLowBalanceEvent;
use App\Models\Business;
use App\Models\LeoChannel;
use Illuminate\Support\Facades\DB;

class VoiceCreditService
{
    public function getBalance(Business $business): int
    {
        return $business->voice_credit_cents;
    }

    public function hasSufficientCredit(Business $business, int $requiredCents): bool
    {
        return $this->hasSufficient($business, $requiredCents);
    }

    public function hasSufficient(Business $business, int $requiredCents): bool
    {
        return $business->voice_credit_cents >= $requiredCents;
    }

    public function deduct(Business $business, int $costCents): void
    {
        DB::transaction(function () use ($business, $costCents): void {
            $lockedBusiness = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            $lockedBusiness->voice_credit_cents = max(0, $lockedBusiness->voice_credit_cents - $costCents);
            $lockedBusiness->save();

            /** @var LeoChannel|null $channel */
            $channel = $lockedBusiness->leoChannel()->where('channel', 'voice')->first();

            if ($lockedBusiness->voice_credit_cents <= 0) {
                $lockedBusiness->forceFill(['voice_auto_call_enabled' => false])->save();

                if ($channel) {
                    $channel->update(['is_active' => false]);
                    VoiceCreditExhaustedEvent::dispatch($lockedBusiness, $channel);
                } else {
                    VoiceCreditExhaustedEvent::dispatch($lockedBusiness, new LeoChannel([
                        'business_id' => $lockedBusiness->id,
                        'channel' => 'voice',
                        'external_identifier' => '',
                        'bot_name' => 'Léo',
                        'is_active' => false,
                    ]));
                }

                return;
            }

            if ($lockedBusiness->voice_credit_cents < config('leo.voice.low_balance_threshold_cents')) {
                VoiceLowBalanceEvent::dispatch($lockedBusiness, $lockedBusiness->voice_credit_cents);
            }
        });
    }

    public function topUp(Business $business, int $amountCents): void
    {
        DB::transaction(function () use ($business, $amountCents): void {
            $lockedBusiness = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            $lockedBusiness->voice_credit_cents += $amountCents;
            $lockedBusiness->voice_auto_call_enabled = true;
            $lockedBusiness->save();

            $lockedBusiness->leoChannel()
                ->where('channel', 'voice')
                ->where('is_active', false)
                ->update(['is_active' => true]);
        });
    }

    public function getCallCost(): int
    {
        return (int) config('services.twilio.voice_cost_per_call_cents', config('leo.voice.cost_call_cents', 8));
    }
}
