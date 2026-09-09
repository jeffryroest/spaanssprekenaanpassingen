<?php

namespace App\Billing;

use App\Models\BillingInvoice;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class IssueBillingInvoice
{
    public function handle(
        Subscription $subscription,
        SubscriptionEvent $event,
        ?SubscriptionOrder $order = null,
    ): BillingInvoice {
        return DB::transaction(function () use ($subscription, $event, $order): BillingInvoice {
            SubscriptionEvent::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            $existing = BillingInvoice::query()
                ->where('subscription_event_id', $event->getKey())
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $order ??= $subscription->orders()->oldest('id')->first();

            if ($order === null) {
                throw new RuntimeException('Voor deze betaling ontbreken factuurgegevens.');
            }

            $issuedAt = $event->occurred_at ?? now();
            $year = (int) $issuedAt->format('Y');
            $now = now();

            DB::table('billing_invoice_sequences')->insertOrIgnore([
                'year' => $year,
                'next_number' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = DB::table('billing_invoice_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new RuntimeException('De factuurnummerreeks kon niet worden geopend.');
            }

            $number = (int) $sequence->next_number;
            DB::table('billing_invoice_sequences')
                ->where('year', $year)
                ->update([
                    'next_number' => $number + 1,
                    'updated_at' => $now,
                ]);

            $prefix = trim((string) config('subscriptions.invoicing.prefix', 'SS'));

            if ($prefix === '' || preg_match('/^[A-Z0-9-]{1,12}$/', $prefix) !== 1) {
                throw new RuntimeException('De factuurprefix is ongeldig geconfigureerd.');
            }

            return BillingInvoice::query()->create([
                'public_id' => (string) Str::ulid(),
                'subscription_id' => $subscription->getKey(),
                'subscription_order_id' => $order->getKey(),
                'subscription_event_id' => $event->getKey(),
                'invoice_year' => $year,
                'sequence_number' => $number,
                'invoice_number' => sprintf('%s-%d-%06d', $prefix, $year, $number),
                'seller_snapshot' => $this->sellerSnapshot(),
                'buyer_snapshot' => $this->buyerSnapshot($order),
                'line_description' => 'Spaansspreken.nl maandabonnement',
                'currency' => $order->currency,
                'amount_minor' => $order->amount_minor,
                'tax_treatment' => 'exempt',
                'tax_rate_basis_points' => 0,
                'tax_minor' => 0,
                'issued_at' => $issuedAt,
            ]);
        });
    }

    /** @return array<string, string> */
    private function sellerSnapshot(): array
    {
        $seller = config('subscriptions.invoicing.seller');

        if (! is_array($seller)) {
            throw new RuntimeException('De factuurafzender ontbreekt.');
        }

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

        foreach ($required as $key) {
            if (! is_string($seller[$key] ?? null) || trim($seller[$key]) === '') {
                throw new RuntimeException('Een verplicht factuurafzenderveld ontbreekt.');
            }
        }

        /** @var array<string, string> $seller */
        return $seller;
    }

    /** @return array<string, string|null> */
    private function buyerSnapshot(SubscriptionOrder $order): array
    {
        return [
            'purchase_type' => $order->purchase_type,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'email' => $order->email,
            'company_name' => $order->company_name,
            'vat_id' => $order->vat_id,
            'street' => $order->billing_street,
            'postal_code' => $order->billing_postal_code,
            'city' => $order->billing_city,
            'country' => $order->billing_country,
        ];
    }
}
