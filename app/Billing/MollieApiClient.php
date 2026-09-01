<?php

namespace App\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class MollieApiClient
{
    public function fetchPayment(string $paymentId): ?MolliePaymentSnapshot
    {
        $apiKey = trim((string) config('services.mollie.api_key'));

        if (! config('services.mollie.enabled') || $apiKey === '') {
            throw new BillingProviderUnavailable('Mollie is niet geconfigureerd.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('services.mollie.connect_timeout', 3)))
                ->timeout(max(1, (int) config('services.mollie.timeout', 8)))
                ->get(rtrim((string) config('services.mollie.base_url'), '/').'/payments/'.$paymentId);
        } catch (ConnectionException) {
            throw new BillingProviderUnavailable('Mollie kon niet worden bereikt.');
        } catch (Throwable) {
            throw new BillingProviderUnavailable('Mollie-verificatie is tijdelijk niet beschikbaar.');
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new BillingProviderUnavailable('Mollie gaf een tijdelijke foutstatus '.$response->status().'.');
        }

        try {
            $data = $response->json();

            if (! is_array($data) || ($data['id'] ?? null) !== $paymentId) {
                throw new BillingProviderUnavailable('Mollie gaf een ongeldige betalingsreferentie terug.');
            }

            $status = $this->requiredString($data, 'status');
            $currency = $this->requiredNestedString($data, 'amount', 'currency');
            $amount = $this->requiredNestedString($data, 'amount', 'value');
            $amountRefunded = $this->optionalNestedString($data, 'amountRefunded', 'value') ?? '0.00';
            $amountChargedBack = $this->optionalNestedString($data, 'amountChargedBack', 'value') ?? '0.00';

            if (! in_array($status, ['open', 'pending', 'authorized', 'paid', 'failed', 'canceled', 'expired'], true)
                || preg_match('/^[A-Z]{3}$/', $currency) !== 1
                || ! $this->isAmount($amount)
                || ! $this->isAmount($amountRefunded)
                || ! $this->isAmount($amountChargedBack)) {
                throw new BillingProviderUnavailable('Mollie gaf een ongeldige betalingsstatus terug.');
            }

            return new MolliePaymentSnapshot(
                id: $paymentId,
                status: $status,
                currency: $currency,
                amount: $amount,
                amountRefunded: $amountRefunded,
                amountChargedBack: $amountChargedBack,
                subscriptionId: $this->optionalString($data, 'subscriptionId'),
                paidAt: $this->optionalString($data, 'paidAt'),
                failedAt: $this->optionalString($data, 'failedAt'),
                canceledAt: $this->optionalString($data, 'canceledAt'),
                expiredAt: $this->optionalString($data, 'expiredAt'),
            );
        } catch (BillingProviderUnavailable $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige betalingsstatus terug.');
        }
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $key): string
    {
        $value = $this->optionalString($data, $key);

        if ($value === null) {
            throw new BillingProviderUnavailable("Mollie-veld {$key} ontbreekt.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' && strlen($value) <= 255 ? $value : null;
    }

    /** @param array<string, mixed> $data */
    private function requiredNestedString(array $data, string $parent, string $key): string
    {
        $value = $this->optionalNestedString($data, $parent, $key);

        if ($value === null) {
            throw new BillingProviderUnavailable("Mollie-veld {$parent}.{$key} ontbreekt.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function optionalNestedString(array $data, string $parent, string $key): ?string
    {
        $parentValue = $data[$parent] ?? null;
        $value = is_array($parentValue) ? ($parentValue[$key] ?? null) : null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function isAmount(string $value): bool
    {
        return preg_match('/^(0|[1-9][0-9]{0,9})\.[0-9]{2}$/', $value) === 1;
    }
}
