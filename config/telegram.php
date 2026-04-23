<?php

return [

    /*
    |----------------------------------------------------------------------
    | Platform Telegram Bot — inbound webhook
    |----------------------------------------------------------------------
    | Path only (no leading slash). CSRF, tenancy and redirect rules read this.
    | Override with TELEGRAM_WEBHOOK_PATH if the URL must differ (keep setWebhook in sync).
    */
    'webhook_path' => env('TELEGRAM_WEBHOOK_PATH', 'webhooks/telegram'),

    /*
    | HTTP path prefix for machine webhooks. RedirectMiddleware and ResolveTenantFromDomain skip
    | tenant logic for any path under {prefix}/. New POST endpoints also need a CSRF except entry
    | in bootstrap/app.php (validateCsrfTokens) unless they are only GET.
    */
    'machine_webhook_path_prefix' => env('TELEGRAM_MACHINE_WEBHOOK_PREFIX', 'webhooks'),

];
