<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Tenant;

/**
 * P1.5: динамический приоритет шагов по {@see SetupProfileRepository} (primary_goal) поверх sortOrder.
 */
final class SetupJourneyOrdering
{
    public function __construct(
        private readonly SetupProfileRepository $profiles,
    ) {}

    /**
     * @param  list<string>  $keys  уже отфильтрованные и в базовом порядке по sortOrder
     * @return list<string>
     */
    public function applyProfileOrdering(Tenant $tenant, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $goal = (string) ($this->profiles->getMerged((int) $tenant->id)['primary_goal'] ?? '');
        $defs = SetupItemRegistry::definitions();
        $indexed = array_values($keys);
        usort(
            $indexed,
            function (string $a, string $b) use ($defs, $goal): int {
                $da = $defs[$a] ?? null;
                $db = $defs[$b] ?? null;
                $pa = $this->goalPenalty($a, $goal);
                $pb = $this->goalPenalty($b, $goal);
                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }

                return ($da?->sortOrder ?? 0) <=> ($db?->sortOrder ?? 0);
            },
        );

        return $indexed;
    }

    /**
     * Меньшее значение — раньше в очереди для данной цели.
     */
    private function goalPenalty(string $key, string $goal): int
    {
        /** @var list<array{0: string, 1: int}> $rules */
        $rules = match ($goal) {
            'booking' => [
                ['contact_channels', -60],
                ['programs.', -50],
                ['pages.home', -40],
                ['settings.site_name', -30],
            ],
            'leads' => [
                ['contact_channels', -60],
                ['pages.home', -50],
                ['settings.site_name', -40],
            ],
            'catalog' => [
                ['pages.home', -60],
                ['programs.', -45],
                ['contact_channels', -35],
            ],
            'info' => [
                ['pages.home', -60],
                ['settings.', -45],
            ],
            default => [],
        };

        $best = 0;
        foreach ($rules as [$prefix, $penalty]) {
            if (str_starts_with($key, $prefix)) {
                $best = min($best, $penalty);
            }
        }

        return $best;
    }
}
