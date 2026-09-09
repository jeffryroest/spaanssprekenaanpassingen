<?php

return [
    'trial_activation_enabled' => (bool) env('SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED', false),
    'checkout_consent_version' => 'mollie-monthly-995-v2',

    /*
    | Dit aanbod is door de producteigenaar vastgesteld. Het wordt niet
    | automatisch in de database geplaatst; gebruik daarvoor bewust het
    | idempotente subscriptions:install-mollie-monthly commando.
    */
    'offers' => [
        'mollie_monthly' => [
            'provider' => 'mollie',
            'code' => 'madrid-maandelijks',
            'name' => 'Spaansspreken Madrid',
            'billing_interval' => 'month',
            'currency' => 'EUR',
            'amount_minor' => 995,
            'trial_days' => 7,
            'entitlements' => ['trial_week'],
        ],
    ],

    /*
    | Goedgekeurd herstelbeleid: veertien dagen toegang na een mislukte
    | maandincasso, met berichten bij start, na zeven dagen en op dag dertien.
    */
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 14),
    'recovery_reminder_days' => [0, 7, 13],

    'invoicing' => [
        'prefix' => env('BILLING_INVOICE_PREFIX', 'SS'),
        'support_email' => env('BILLING_SUPPORT_EMAIL', 'support@spaansspreken.nl'),
        'seller' => [
            'legal_name' => env('BILLING_SELLER_LEGAL_NAME', ''),
            'trade_name' => env('BILLING_SELLER_TRADE_NAME', 'Spaansspreken.nl'),
            'street' => env('BILLING_SELLER_STREET', ''),
            'postal_code' => env('BILLING_SELLER_POSTAL_CODE', ''),
            'city' => env('BILLING_SELLER_CITY', ''),
            'country' => env('BILLING_SELLER_COUNTRY', 'NL'),
            'chamber_of_commerce' => env('BILLING_SELLER_COC', ''),
            'vat_id' => env('BILLING_SELLER_VAT_ID', ''),
        ],
        'tax' => [
            'treatment' => 'exempt',
            'rate_basis_points' => 0,
            'reason' => 'Vrijgesteld van btw wegens taalonderwijs.',
        ],
    ],
];
