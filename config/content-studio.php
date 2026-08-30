<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Review policy
    |--------------------------------------------------------------------------
    |
    | "risk_based" allows an administrator or editor-in-chief to approve an
    | ordinary revision they created, while high-risk content still requires
    | an independent reviewer. Set this to "strict" when every revision must
    | always be reviewed by a second person.
    |
    */
    'review_mode' => env('CONTENT_STUDIO_REVIEW_MODE', 'risk_based'),

    'demo_actor_email' => env('CONTENT_STUDIO_DEMO_ACTOR_EMAIL'),
];
