<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default tenant mail rate (fallback)
    |--------------------------------------------------------------------------
    |
    | Used when tenant.mail_rate_limit_per_minute is missing or invalid.
    | Per-tenant value is edited in Platform admin (client card).
    |
    */
    'default_per_minute' => (int) env('MAIL_TENANT_PER_MINUTE', 10),

    /*
    |--------------------------------------------------------------------------
    | Hard safety clamp
    |--------------------------------------------------------------------------
    */
    'max_per_minute' => 1000,

    'min_per_minute' => 1,

];
