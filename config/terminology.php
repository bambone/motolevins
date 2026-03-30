<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log terminology fallbacks
    |--------------------------------------------------------------------------
    |
    | When a term has no default_label in DB and no preset row, the resolver uses
    | emergency RU map or Latin headline. Set to true to log those cases in local
    | / debug for data-quality monitoring.
    |
    */
    'log_fallbacks' => env('TERMINOLOGY_LOG_FALLBACKS', true),

];
