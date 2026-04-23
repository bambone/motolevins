<?php

namespace App\Http\Middleware;

use App\Models\TenantDomain;
use App\Tenant\HostClassifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the Telegram inbound POST route to intentional public hosts (not tenant sites).
 * Path: {@see config('telegram.webhook_path')}.
 *
 * Policy: accept if the host is "non-tenant" (central marketing or platform panel) per {@see HostClassifier},
 * or matches the host of {@see config('app.url')} (single-app / API URL deployments).
 * This reduces accidental exposure on tenant subdomains while keeping one canonical URL in docs.
 */
final class EnsureTelegramWebhookHost
{
    public function __construct(
        private readonly HostClassifier $hostClassifier,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim((string) config('telegram.webhook_path', 'webhooks/telegram'), '/');
        if ($path === '' || ! $request->is($path)) {
            return $next($request);
        }

        $host = TenantDomain::normalizeHost($request->getHost());
        if ($this->isAllowedWebhookHost($host)) {
            return $next($request);
        }

        return response()->json([
            'ok' => false,
            'error' => 'webhook_host_not_allowed',
        ], 404);
    }

    private function isAllowedWebhookHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        if ($this->hostClassifier->isNonTenantHost($host)) {
            return true;
        }

        $appHost = $this->hostFromAppUrl();
        if ($appHost !== '' && $appHost === $host) {
            return true;
        }

        if (app()->isLocal() && in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return true;
        }

        return false;
    }

    private function hostFromAppUrl(): string
    {
        $url = (string) config('app.url', '');
        if ($url === '') {
            return '';
        }
        $parsed = parse_url($url);
        if (! is_array($parsed) || ! isset($parsed['host'])) {
            return '';
        }

        return TenantDomain::normalizeHost((string) $parsed['host']);
    }
}
