<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoiceCreditRenewedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly int $amountCents,
        public readonly int $newBalanceCents,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Crédits Appels Léo renouvelés',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leo.voice-renewed',
        );
    }
}
