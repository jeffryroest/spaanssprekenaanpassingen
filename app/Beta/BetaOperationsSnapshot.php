<?php

namespace App\Beta;

use App\ContentStudio\RuntimeReadiness;
use App\Enums\SubscriptionStatus;
use App\Models\BillingEmailDelivery;
use App\Models\Subscription;
use Carbon\CarbonImmutable;

final class BetaOperationsSnapshot
{
    public function __construct(
        private readonly RuntimeReadiness $runtimeReadiness,
        private readonly SchedulerHeartbeat $schedulerHeartbeat,
    ) {}

    /**
     * @return array{
     *   checks: list<array{key: string, label: string, ready: bool, detail: string, action: string}>,
     *   ready_count: int,
     *   check_count: int,
     *   incidents: array{past_due: int, paused: int, overdue_emails: int, exhausted_emails: int},
     *   scheduler_last_seen_at: ?CarbonImmutable
     * }
     */
    public function current(): array
    {
        $runtimeItems = $this->runtimeReadiness->items();
        $runtimeReadyCount = collect($runtimeItems)->where('ready', true)->count();
        $overdueEmails = BillingEmailDelivery::query()
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->where('attempt_count', '<', 5)
            ->where('due_at', '<=', now()->subMinutes(30))
            ->count();
        $exhaustedEmails = BillingEmailDelivery::query()
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->where('attempt_count', '>=', 5)
            ->count();
        $checks = [
            $this->check(
                'environment',
                'Veilige productieomgeving',
                app()->environment('production') && ! config('app.debug') && str_starts_with((string) config('app.url'), 'https://'),
                'Productiemodus, debug uit en een HTTPS-app-URL.',
                'Controleer APP_ENV, APP_DEBUG en APP_URL in Ploi.',
            ),
            $this->check(
                'content',
                'Speelcontent gepubliceerd',
                $runtimeReadyCount === count($runtimeItems) && $runtimeItems !== [],
                "{$runtimeReadyCount}/".count($runtimeItems).' vereiste runtimecontracten zijn speelbaar.',
                'Review en publiceer de ontbrekende content of media in de Content Studio.',
            ),
            $this->check(
                'mail',
                'Transactiemail geconfigureerd',
                $this->mailIsConfigured(),
                'De mailer en vaste afzender zijn ingesteld zonder configuratiewaarden te tonen.',
                'Configureer Postmark SMTP of de Postmark API-driver en support@spaansspreken.nl als afzender.',
            ),
            $this->check(
                'mollie',
                'Mollie-checkout geactiveerd',
                (bool) config('services.mollie.enabled')
                    && (bool) config('services.mollie.checkout_enabled')
                    && filled(config('services.mollie.api_key')),
                'Providerverificatie, checkoutschakelaar en API-sleutel zijn gecontroleerd op aanwezigheid.',
                'Controleer MOLLIE_BILLING_ENABLED, MOLLIE_CHECKOUT_ENABLED en MOLLIE_API_KEY.',
            ),
            $this->check(
                'invoicing',
                'Factuurafzender compleet',
                $this->invoiceSellerIsComplete(),
                'Alle verplichte afzender- en supportvelden zijn aanwezig; de waarden worden hier niet getoond.',
                'Vul de BILLING_SELLER_* en BILLING_SUPPORT_EMAIL variabelen volledig in.',
            ),
            $this->check(
                'scheduler',
                'Scheduler recent actief',
                $this->schedulerHeartbeat->isFresh(),
                'De heartbeat moet minder dan vijf minuten oud zijn.',
                'Laat in Ploi iedere minuut php artisan schedule:run uitvoeren.',
            ),
            $this->check(
                'billing_email_queue',
                'Betaalmails worden verwerkt',
                $overdueEmails === 0 && $exhaustedEmails === 0,
                "{$overdueEmails} langer dan 30 minuten open; {$exhaustedEmails} na vijf pogingen gestopt.",
                'Controleer Postmark en voer billing:send-due-emails gecontroleerd uit.',
            ),
        ];

        return [
            'checks' => $checks,
            'ready_count' => collect($checks)->where('ready', true)->count(),
            'check_count' => count($checks),
            'incidents' => [
                'past_due' => Subscription::query()->where('status', SubscriptionStatus::PastDue)->count(),
                'paused' => Subscription::query()->where('status', SubscriptionStatus::Paused)->count(),
                'overdue_emails' => $overdueEmails,
                'exhausted_emails' => $exhaustedEmails,
            ],
            'scheduler_last_seen_at' => $this->schedulerHeartbeat->lastSeenAt(),
        ];
    }

    /** @return array{key: string, label: string, ready: bool, detail: string, action: string} */
    private function check(string $key, string $label, bool $ready, string $detail, string $action): array
    {
        return compact('key', 'label', 'ready', 'detail', 'action');
    }

    private function mailIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');
        $fromAddress = (string) config('mail.from.address');

        if (in_array($mailer, ['', 'log', 'array'], true) || $fromAddress !== 'support@spaansspreken.nl') {
            return false;
        }

        if ($mailer === 'postmark') {
            return filled(config('services.postmark.key'));
        }

        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host'))
                && filled(config('mail.mailers.smtp.username'))
                && filled(config('mail.mailers.smtp.password'));
        }

        return true;
    }

    private function invoiceSellerIsComplete(): bool
    {
        $seller = (array) config('subscriptions.invoicing.seller', []);
        $required = [
            'legal_name',
            'trade_name',
            'street',
            'postal_code',
            'city',
            'country',
            'chamber_of_commerce',
            'vat_id',
        ];

        return collect($required)->every(fn (string $key): bool => filled($seller[$key] ?? null))
            && filter_var(config('subscriptions.invoicing.support_email'), FILTER_VALIDATE_EMAIL) !== false;
    }
}
