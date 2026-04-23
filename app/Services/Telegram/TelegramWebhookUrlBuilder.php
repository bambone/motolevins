<?php

namespace App\Services\Telegram;

use App\Services\Seo\PlatformMarketingPublicBaseUrl;

/**
 * Builds the public webhook URL from a canonical base (never from Request::getHost()).
 */
final class TelegramWebhookUrlBuilder
{
    public function __construct(
        private readonly PlatformMarketingPublicBaseUrl $marketingBaseUrl,
    ) {}

    public function telegramWebhookUrl(): string
    {
        $path = ltrim((string) config('telegram.webhook_path', 'webhooks/telegram'), '/');

        return $this->canonicalBase().'/'.$path;
    }

    /**
     * Prefer APP_URL when it is a usable absolute URL; otherwise fall back to marketing apex / app.url.
     */
    private function canonicalBase(): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && $appUrl !== 'http://' && $appUrl !== 'https://') {
            $parsed = parse_url($appUrl) ?: [];
            if (isset($parsed['host']) && (string) $parsed['host'] !== '') {
                return $appUrl;
            }
        }

        return rtrim($this->marketingBaseUrl->resolve(), '/');
    }
}
