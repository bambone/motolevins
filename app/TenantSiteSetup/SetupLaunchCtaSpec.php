<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;
use App\Models\User;

/**
 * Единая логика primary CTA для обзора запуска и виджета дашборда.
 */
final class SetupLaunchCtaSpec
{
    public function __construct(
        private readonly SetupSessionService $sessions,
    ) {}

    /**
     * @param  array<string, mixed>  $summary  {@see SetupProgressService::summary}
     * @return array{label: string, href: string}
     */
    public function dashboardPrimary(Tenant $tenant, User $user, array $summary, string $overviewAbsoluteUrl): array
    {
        $hasPaused = $this->sessions->pausedSession($tenant, $user) !== null;
        $hasActive = $this->sessions->activeSession($tenant, $user) !== null;
        $remaining = (int) ($summary['launch_critical_remaining'] ?? 0);
        $sep = str_contains($overviewAbsoluteUrl, '?') ? '&' : '?';

        if ($hasPaused || $hasActive) {
            return [
                'label' => 'Продолжить запуск',
                'href' => $overviewAbsoluteUrl.$sep.'start_guided=1',
            ];
        }

        if ($remaining > 0) {
            return [
                'label' => 'Начать запуск',
                'href' => $overviewAbsoluteUrl.$sep.'start_guided=1',
            ];
        }

        return [
            'label' => 'Открыть обзор запуска',
            'href' => $overviewAbsoluteUrl,
        ];
    }
}
