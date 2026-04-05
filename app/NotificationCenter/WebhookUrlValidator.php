<?php

namespace App\NotificationCenter;

use Illuminate\Support\Str;

/**
 * Outbound webhook SSRF guard: https only, no private/link-local IPs.
 */
final class WebhookUrlValidator
{
    public function assertSafeHttpsUrl(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            throw new \InvalidArgumentException('Webhook URL is empty.');
        }

        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') {
            throw new \InvalidArgumentException('Webhook URL must use https.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            throw new \InvalidArgumentException('Webhook URL has no host.');
        }

        if (Str::endsWith($host, '.local') || Str::endsWith($host, '.localhost')) {
            throw new \InvalidArgumentException('Webhook host is not allowed.');
        }

        $blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        if (in_array($host, $blocked, true)) {
            throw new \InvalidArgumentException('Webhook host is not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        $ip = @gethostbyname($host);
        if ($ip === $host || $ip === '') {
            throw new \InvalidArgumentException('Webhook host could not be resolved.');
        }

        $this->assertPublicIp($ip);
    }

    private function assertPublicIp(string $ip): void
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \InvalidArgumentException('Webhook resolves to a disallowed IP range.');
        }
    }
}
