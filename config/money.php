<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Strict money bindings (local / testing)
    |--------------------------------------------------------------------------
    |
    | When true, formatting or parsing with an unknown binding key throws.
    | In production, set false to log and use a degraded fallback.
    |
    */
    'strict_bindings' => env('MONEY_STRICT_BINDINGS') !== null
        ? filter_var(env('MONEY_STRICT_BINDINGS'), FILTER_VALIDATE_BOOLEAN)
        : env('APP_ENV') !== 'production',

];
