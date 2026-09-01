<?php

namespace App\Billing;

use App\Models\SubscriptionOrder;

final readonly class MollieCheckout
{
    public function __construct(
        public SubscriptionOrder $order,
        public string $checkoutUrl,
    ) {}
}
