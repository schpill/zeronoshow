<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoiceCreditExhaustedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Crédits Appels épuisés',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leo.voice-exhausted',
        );
    }
}
