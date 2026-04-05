<?php

return [
    /**
     * Phase A/B migration: when true, legacy SendLeadTelegramNotification / SendBookingTelegramNotification still dispatch.
     */
    'legacy_telegram_parallel' => (bool) env('NOTIFICATION_LEGACY_TELEGRAM_PARALLEL', false),

    'webhook' => [
        'timeout_seconds' => (int) env('NOTIFICATION_WEBHOOK_TIMEOUT', 10),
        'max_payload_kb' => (int) env('NOTIFICATION_WEBHOOK_MAX_KB', 256),
        'max_redirects' => 0,
    ],

    'webpush' => [
        'vapid_subject' => env('NOTIFICATION_VAPID_SUBJECT', 'mailto:noreply@example.com'),
    ],
];
