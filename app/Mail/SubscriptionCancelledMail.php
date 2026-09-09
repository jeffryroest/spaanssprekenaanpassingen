<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class SubscriptionCancelledMail extends Mailable
{
    public function __construct(public readonly Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Je abonnement op Spaansspreken.nl is opgezegd');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.billing.subscription-cancelled');
    }
}
