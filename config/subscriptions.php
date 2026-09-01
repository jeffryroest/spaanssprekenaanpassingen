<?php

return [
    'trial_activation_enabled' => (bool) env('SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED', false),

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
    | De producteigenaar moet een eventuele betaalachterstand-graceperiode
    | expliciet goedkeuren. Tot dat besluit geeft past_due geen extra dagen.
    */
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 0),
];
