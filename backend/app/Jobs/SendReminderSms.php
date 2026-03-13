<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendReminderSms implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $reservationId,
        public string $reminderType,
        public bool $preclaimed = false,
    ) {}

    public function handle(SmsServiceInterface $sms): void
    {
        $reservation = Reservation::query()
            ->with(['customer', 'business'])
            ->find($this->reservationId);

        if (! $reservation) {
            return;
        }

        if ($reservation->customer->opted_out) {
            return;
        }

        if (in_array($reservation->status, ['cancelled_by_client', 'cancelled_no_confirmation', 'no_show'], true)) {
            return;
        }

        if ($this->reminderType === '2h' && $reservation->reminder_2h_sent && ! $this->preclaimed) {
            return;
        }

        if ($this->reminderType === '30m' && $reservation->reminder_30m_sent && ! $this->preclaimed) {
            return;
        }

        if (! $reservation->confirmation_token) {
            $reservation->forceFill([
                'confirmation_token' => (string) Str::uuid(),
                'token_expires_at' => $reservation->scheduled_at->copy()->subMinutes(15),
            ])->save();
            $reservation->refresh();
        }

        $showUrl = route('confirmation.show', $reservation->confirmation_token);
        $cancelUrl = route('confirmation.cancel', $reservation->confirmation_token);
        $businessName = $reservation->business->name;
        $customerName = $reservation->customer_name;
        $time = $reservation->scheduled_at->timezone($reservation->business->timezone)->format('H:i');

        $body = match ($this->reminderType) {
            '30m' => sprintf(
                'Bonjour %s, Dernier rappel: votre RDV dans 30 min chez %s à %s. Confirmez: %s',
                $customerName,
                $businessName,
                $time,
                $showUrl,
            ),
            '2h' => $reservation->customer->getScoreTier() === 'at_risk'
                ? sprintf(
                    'Bonjour %s, rappel URGENT: votre RDV est dans 2h chez %s. Confirmez impérativement: %s ou annulez: %s',
                    $customerName,
                    $businessName,
                    $showUrl,
                    $cancelUrl,
                )
                : sprintf(
                    'Bonjour %s, rappel: votre RDV est dans 2h chez %s à %s. Confirmez: %s',
                    $customerName,
                    $businessName,
                    $time,
                    $showUrl,
                ),
            default => sprintf(
                'Bonjour %s, rappel: votre RDV est dans 2h chez %s à %s. Confirmez: %s',
                $customerName,
                $businessName,
                $time,
                $showUrl,
            ),
        };

        $smsLog = SmsLog::query()->create([
            'reservation_id' => $reservation->id,
            'business_id' => $reservation->business_id,
            'phone' => $reservation->customer->phone,
            'type' => $this->reminderType === '30m' ? 'reminder_30m' : 'reminder_2h',
            'body' => $body,
            'status' => 'queued',
            'queued_at' => now(),
            'created_at' => now(),
        ]);

        $sms->send($smsLog);

        $reservation->forceFill([
            'reminder_2h_sent' => $this->reminderType === '2h' ? true : $reservation->reminder_2h_sent,
            'reminder_30m_sent' => $this->reminderType === '30m' ? true : $reservation->reminder_30m_sent,
        ])->save();
    }
}
