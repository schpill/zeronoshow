<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoiceLowBalanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly int $balanceCents,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Léo Voice — crédit faible',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leo.voice-low-balance',
        );
    }
}
