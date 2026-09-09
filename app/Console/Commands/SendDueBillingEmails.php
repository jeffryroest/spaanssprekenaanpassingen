<?php

namespace App\Console\Commands;

use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentRecoveryMail;
use App\Mail\SubscriptionCancelledMail;
use App\Models\BillingEmailDelivery;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendDueBillingEmails extends Command
{
    protected $signature = 'billing:send-due-emails {--limit=100}';

    protected $description = 'Verstuur geplande betaalmails idempotent zonder persoonsgegevens te loggen';

    public function handle(): int
    {
        $limit = min(500, max(1, (int) $this->option('limit')));
        $deliveries = BillingEmailDelivery::query()
            ->with(['subscription.orders', 'invoice'])
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->where('due_at', '<=', now())
            ->where('attempt_count', '<', 5)
            ->oldest('due_at')
            ->limit($limit)
            ->get();
        $sent = 0;

        foreach ($deliveries as $delivery) {
            $recipient = $delivery->subscription->orders->sortBy('id')->first()?->email;

            if (! is_string($recipient) || $recipient === '') {
                $delivery->forceFill([
                    'attempt_count' => $delivery->attempt_count + 1,
                    'last_error_code' => 'recipient_missing',
                ])->save();

                continue;
            }

            $delivery->increment('attempt_count');

            try {
                Mail::to($recipient)->send($this->mailable($delivery));
                $delivery->forceFill([
                    'sent_at' => now(),
                    'last_error_code' => null,
                ])->save();
                $sent++;
            } catch (Throwable) {
                $delivery->forceFill(['last_error_code' => 'mail_delivery_failed'])->save();
            }
        }

        $this->info("{$sent} betaalmail(s) verstuurd.");

        return self::SUCCESS;
    }

    private function mailable(BillingEmailDelivery $delivery): Mailable
    {
        if ($delivery->kind === 'payment_confirmed' && $delivery->invoice !== null) {
            return new PaymentConfirmedMail($delivery->invoice);
        }

        if (str_starts_with($delivery->kind, 'payment_recovery_day_')) {
            return new PaymentRecoveryMail(
                $delivery->subscription,
                (int) str_replace('payment_recovery_day_', '', $delivery->kind),
            );
        }

        if ($delivery->kind === 'subscription_cancelled') {
            return new SubscriptionCancelledMail($delivery->subscription);
        }

        throw new \RuntimeException('Onbekend betaalmailtype.');
    }
}
