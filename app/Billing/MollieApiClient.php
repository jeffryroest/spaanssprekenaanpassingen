<?php

namespace App\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class MollieApiClient
{
    public function fetchPayment(string $paymentId): ?MolliePaymentSnapshot
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->get($this->url('/payments/'.$paymentId)));

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
                customerId: $this->optionalString($data, 'customerId'),
                subscriptionId: $this->optionalString($data, 'subscriptionId'),
                sequenceType: $this->optionalString($data, 'sequenceType'),
                checkoutReference: $this->optionalMetadataString($data, 'checkout_reference'),
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

    public function createCustomer(
        string $name,
        string $email,
        string $checkoutReference,
        string $idempotencyKey,
    ): string {
        $response = $this->send(fn (PendingRequest $request): Response => $request
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($this->url('/customers'), [
                'name' => $name,
                'email' => $email,
                'metadata' => ['checkout_reference' => $checkoutReference],
            ]));
        $data = $this->successfulJson($response, [200, 201]);
        $customerId = $this->requiredString($data, 'id');

        if (preg_match('/^cst_[A-Za-z0-9]{6,64}$/', $customerId) !== 1) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige klantreferentie terug.');
        }

        return $customerId;
    }

    public function createFirstPayment(
        string $customerId,
        int $amountMinor,
        string $currency,
        string $description,
        string $redirectUrl,
        string $cancelUrl,
        string $webhookUrl,
        string $checkoutReference,
        string $idempotencyKey,
    ): MollieCreatedPayment {
        $response = $this->send(fn (PendingRequest $request): Response => $request
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($this->url('/customers/'.$customerId.'/payments'), [
                'amount' => ['currency' => $currency, 'value' => $this->amountValue($amountMinor)],
                'description' => $description,
                'sequenceType' => 'first',
                'redirectUrl' => $redirectUrl,
                'cancelUrl' => $cancelUrl,
                'webhookUrl' => $webhookUrl,
                'metadata' => ['checkout_reference' => $checkoutReference],
            ]));
        $data = $this->successfulJson($response, [200, 201]);
        $paymentId = $this->requiredString($data, 'id');
        $status = $this->requiredString($data, 'status');
        $links = $data['_links'] ?? null;
        $checkoutUrl = is_array($links)
            ? $this->requiredNestedString($links, 'checkout', 'href')
            : throw new BillingProviderUnavailable('Mollie gaf geen checkout-link terug.');

        if (preg_match('/^tr_[A-Za-z0-9]{8,64}$/', $paymentId) !== 1
            || ! in_array($status, ['open', 'pending', 'authorized', 'paid'], true)
            || ! str_starts_with($checkoutUrl, 'https://')) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige checkout terug.');
        }

        return new MollieCreatedPayment($paymentId, $status, $checkoutUrl);
    }

    public function hasUsableMandate(string $customerId): bool
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->get(
            $this->url('/customers/'.$customerId.'/mandates'),
        ));
        $data = $this->successfulJson($response);
        $mandates = $data['_embedded']['mandates'] ?? [];

        return is_array($mandates) && collect($mandates)->contains(
            fn (mixed $mandate): bool => is_array($mandate)
                && in_array($mandate['status'] ?? null, ['pending', 'valid'], true),
        );
    }

    public function findSubscriptionForOrder(string $customerId, string $checkoutReference): ?string
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->get(
            $this->url('/customers/'.$customerId.'/subscriptions'),
            ['limit' => 250],
        ));
        $data = $this->successfulJson($response);
        $subscriptions = $data['_embedded']['subscriptions'] ?? [];

        if (! is_array($subscriptions)) {
            return null;
        }

        foreach ($subscriptions as $subscription) {
            if (! is_array($subscription)
                || $this->optionalMetadataString($subscription, 'checkout_reference') !== $checkoutReference) {
                continue;
            }

            $subscriptionId = $this->optionalString($subscription, 'id');

            if ($subscriptionId !== null && preg_match('/^sub_[A-Za-z0-9]{6,64}$/', $subscriptionId) === 1) {
                return $subscriptionId;
            }
        }

        return null;
    }

    public function createSubscription(
        string $customerId,
        int $amountMinor,
        string $currency,
        string $startDate,
        string $description,
        string $webhookUrl,
        string $checkoutReference,
        string $idempotencyKey,
    ): string {
        $response = $this->send(fn (PendingRequest $request): Response => $request
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($this->url('/customers/'.$customerId.'/subscriptions'), [
                'amount' => ['currency' => $currency, 'value' => $this->amountValue($amountMinor)],
                'interval' => '1 month',
                'startDate' => $startDate,
                'description' => $description,
                'webhookUrl' => $webhookUrl,
                'metadata' => ['checkout_reference' => $checkoutReference],
            ]));
        $data = $this->successfulJson($response, [200, 201]);
        $subscriptionId = $this->requiredString($data, 'id');

        if (preg_match('/^sub_[A-Za-z0-9]{6,64}$/', $subscriptionId) !== 1) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige abonnementsreferentie terug.');
        }

        return $subscriptionId;
    }

    public function cancelSubscription(string $customerId, string $subscriptionId): void
    {
        $response = $this->send(fn (PendingRequest $request): Response => $request->delete(
            $this->url('/customers/'.$customerId.'/subscriptions/'.$subscriptionId),
        ));

        if (! in_array($response->status(), [200, 204, 404], true)) {
            throw new BillingProviderUnavailable('Mollie kon het abonnement niet opzeggen.');
        }
    }

    /** @param callable(PendingRequest): Response $callback */
    private function send(callable $callback): Response
    {
        $apiKey = trim((string) config('services.mollie.api_key'));

        if (! config('services.mollie.enabled') || $apiKey === '') {
            throw new BillingProviderUnavailable('Mollie is niet geconfigureerd.');
        }

        try {
            return $callback(Http::withToken($apiKey)
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('services.mollie.connect_timeout', 3)))
                ->timeout(max(1, (int) config('services.mollie.timeout', 8))));
        } catch (BillingProviderUnavailable $exception) {
            throw $exception;
        } catch (ConnectionException) {
            throw new BillingProviderUnavailable('Mollie kon niet worden bereikt.');
        } catch (Throwable) {
            throw new BillingProviderUnavailable('Mollie-verificatie is tijdelijk niet beschikbaar.');
        }
    }

    /**
     * @param  array<int, int>  $allowedStatuses
     * @return array<string, mixed>
     */
    private function successfulJson(Response $response, array $allowedStatuses = [200]): array
    {
        if (! in_array($response->status(), $allowedStatuses, true)) {
            throw new BillingProviderUnavailable('Mollie gaf een tijdelijke foutstatus '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldig antwoord terug.');
        }

        return $data;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.mollie.base_url'), '/').$path;
    }

    private function amountValue(int $amountMinor): string
    {
        return sprintf('%d.%02d', intdiv($amountMinor, 100), $amountMinor % 100);
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

    /** @param array<string, mixed> $data */
    private function optionalMetadataString(array $data, string $key): ?string
    {
        $metadata = $data['metadata'] ?? null;
        $value = is_array($metadata) ? ($metadata[$key] ?? null) : null;

        return is_string($value) && $value !== '' && strlen($value) <= 255 ? $value : null;
    }

    private function isAmount(string $value): bool
    {
        return preg_match('/^(0|[1-9][0-9]{0,9})\.[0-9]{2}$/', $value) === 1;
    }
}
