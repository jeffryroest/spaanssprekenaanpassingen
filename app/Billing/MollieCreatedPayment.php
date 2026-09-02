<?php

namespace App\Billing;

final readonly class MollieCreatedPayment
{
    public function __construct(
        public string $id,
        public string $status,
        public string $checkoutUrl,
    ) {}
}
