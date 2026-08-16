<?php

return [
    /*
    | De producteigenaar moet een eventuele betaalachterstand-graceperiode
    | expliciet goedkeuren. Tot dat besluit geeft past_due geen extra dagen.
    */
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 0),
];
