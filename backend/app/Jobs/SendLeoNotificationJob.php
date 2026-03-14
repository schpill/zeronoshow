<?php

namespace App\Jobs;

use App\Leo\Tools\LeoThrottleService;
use App\Leo\Tools\TelegramChannel;
use App\Models\LeoChannel;
use App\Models\LeoMessageLog;
use App\Models\Reservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLeoNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $reservationId,
        public string $event,
    ) {
        $this->onQueue('default');
    }

    public function handle(LeoThrottleService $throttleService, TelegramChannel $telegramChannel): void
    {
        $reservation = Reservation::query()->with('business')->find($this->reservationId);

        if (! $reservation) {
            return;
        }

        $channel = LeoChannel::query()
            ->where('business_id', $reservation->business_id)
            ->where('is_active', true)
            ->first();

        if (! $channel) {
            return;
        }

        $throttleKey = "{$channel->channel}:{$channel->external_identifier}";

        if ($throttleService->isThrottled($throttleKey)) {
            LeoMessageLog::query()->create([
                'channel_id' => $channel->id,
                'direction' => 'outbound',
                'sender_identifier' => $channel->external_identifier,
                'raw_message' => 'Notification Léo bloquée par le throttle.',
                'intent' => 'throttled',
                'response_preview' => 'Throttled',
                'created_at' => now(),
            ]);

            return;
        }

        $message = match ($this->event) {
            'cancelled_by_client' => sprintf(
                'Annulation client: %s pour %s.',
                $reservation->customer_name,
                $reservation->scheduled_at->timezone($reservation->business->timezone)->format('d/m H:i'),
            ),
            'no_show' => sprintf(
                'No-show: %s pour %s.',
                $reservation->customer_name,
                $reservation->scheduled_at->timezone($reservation->business->timezone)->format('d/m H:i'),
            ),
            default => sprintf('Mise a jour Léo: %s.', $reservation->customer_name),
        };

        if ($channel->channel === 'telegram') {
            $telegramChannel->sendMessage($channel->external_identifier, $message);
        }

        $throttleService->increment($throttleKey);

        LeoMessageLog::query()->create([
            'channel_id' => $channel->id,
            'direction' => 'outbound',
            'sender_identifier' => $channel->external_identifier,
            'raw_message' => $message,
            'intent' => $this->event,
            'response_preview' => mb_substr($message, 0, 120),
            'created_at' => now(),
        ]);
    }
}
