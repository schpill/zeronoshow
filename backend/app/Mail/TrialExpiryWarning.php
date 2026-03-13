<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiryWarning extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Business $business) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre essai ZeroNoShow expire dans 48h',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expiry',
            text: 'emails.trial-expiry-text',
            with: [
                'business' => $this->business,
                'subscriptionUrl' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/subscription',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
