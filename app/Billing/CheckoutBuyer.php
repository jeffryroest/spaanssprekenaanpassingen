<?php

namespace App\Billing;

final readonly class CheckoutBuyer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $purchaseType,
        public ?string $companyName,
        public ?string $vatId,
        public ?string $billingStreet,
        public ?string $billingPostalCode,
        public ?string $billingCity,
        public ?string $billingCountry,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        $business = $validated['purchase_type'] === 'business';

        return new self(
            firstName: trim((string) $validated['first_name']),
            lastName: trim((string) $validated['last_name']),
            email: mb_strtolower(trim((string) $validated['email'])),
            purchaseType: $business ? 'business' : 'individual',
            companyName: $business ? trim((string) $validated['company_name']) : null,
            vatId: $business ? mb_strtoupper(trim((string) $validated['vat_id'])) : null,
            billingStreet: $business ? trim((string) $validated['billing_street']) : null,
            billingPostalCode: $business ? mb_strtoupper(trim((string) $validated['billing_postal_code'])) : null,
            billingCity: $business ? trim((string) $validated['billing_city']) : null,
            billingCountry: $business ? mb_strtoupper(trim((string) $validated['billing_country'])) : null,
        );
    }

    /** @return array<string, string|null> */
    public function orderAttributes(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'purchase_type' => $this->purchaseType,
            'company_name' => $this->companyName,
            'vat_id' => $this->vatId,
            'billing_street' => $this->billingStreet,
            'billing_postal_code' => $this->billingPostalCode,
            'billing_city' => $this->billingCity,
            'billing_country' => $this->billingCountry,
        ];
    }
}
