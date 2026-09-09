<?php

namespace App\Mail;

use App\Billing\InvoicePdf;
use App\Models\BillingInvoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PaymentConfirmedMail extends Mailable
{
    public function __construct(public readonly BillingInvoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Betaling bevestigd · '.$this->invoice->invoice_number);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.billing.payment-confirmed');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => app(InvoicePdf::class)->render($this->invoice),
                $this->invoice->invoice_number.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
