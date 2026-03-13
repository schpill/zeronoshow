<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedStub extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly string $invoiceId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement ZeroNoShow à vérifier',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.payment-failed-stub',
            with: [
                'business' => $this->business,
                'invoiceId' => $this->invoiceId,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
