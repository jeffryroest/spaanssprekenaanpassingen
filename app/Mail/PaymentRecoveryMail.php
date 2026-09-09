<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PaymentRecoveryMail extends Mailable
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly int $reminderDay,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Actie nodig: je betaling voor Spaansspreken.nl');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.billing.payment-recovery');
    }
}
