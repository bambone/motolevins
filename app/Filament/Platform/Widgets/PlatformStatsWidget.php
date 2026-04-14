<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Tenant;
use App\Models\TenantMailLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformStatsWidget extends BaseStatsOverviewWidget
{
    /**
     * Без авто-poll: дефолт Filament StatsOverview — wire:poll.5s, на тяжёлом getStats() даёт поток
     * /livewire/update и ощущение «залипшей» вкладки. Обновление — по F5 или отдельной кнопке позже.
     */
    protected ?string $pollingInterval = null;

    /** См. PlatformDashboardIntroWidget: синхронный рендер, без залипшего loading-placeholder. */
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected static ?string $panel = 'platform';

    private const CACHE_TTL_SECONDS = 45;

    private const CACHE_KEY = 'filament.platform.stats_widget.payload_v2';

    protected function getStats(): array
    {
        $payload = Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->computeStatsPayload(),
        );

        return $this->statsFromPayload($payload);
    }

    /**
     * @return array{
     *     activeClients: int,
     *     trialClients: int,
     *     newTenantsLast7Days: int,
     *     sentMails: int,
     *     errorMails: int,
     *     throttledRows: int,
     *     clientsChart: list<int>,
     *     sentChart: list<int>,
     *     failedChart: list<int>,
     *     throttledChart: list<int>,
     * }
     */
    private function computeStatsPayload(): array
    {
        $activeClients = (int) Tenant::query()->where('status', 'active')->count();
        $trialClients = (int) Tenant::query()->where('status', 'trial')->count();

        $clientsByDay = $this->tenantCreationsByDay();
        $clientsChart = $this->fillSevenDaySeries($clientsByDay);
        $newTenantsLast7Days = (int) array_sum($clientsChart);

        $sentMails = (int) TenantMailLog::query()->where('status', TenantMailLog::STATUS_SENT)->count();
        $errorMails = (int) TenantMailLog::query()->where('status', TenantMailLog::STATUS_FAILED)->count();
        $throttledRows = (int) TenantMailLog::query()->where('throttled_count', '>', 0)->count();

        $sentByDay = $this->mailLogsByEffectiveDay(
            TenantMailLog::query()->where('status', TenantMailLog::STATUS_SENT),
            $this->mailEffectiveDateSql('sent'),
        );
        $failedByDay = $this->mailLogsByEffectiveDay(
            TenantMailLog::query()->where('status', TenantMailLog::STATUS_FAILED),
            $this->mailEffectiveDateSql('failed'),
        );
        $throttledByDay = $this->mailLogsByDayColumn(
            TenantMailLog::query()
                ->where('throttled_count', '>', 0),
            'created_at',
        );

        return [
            'activeClients' => $activeClients,
            'trialClients' => $trialClients,
            'newTenantsLast7Days' => $newTenantsLast7Days,
            'sentMails' => $sentMails,
            'errorMails' => $errorMails,
            'throttledRows' => $throttledRows,
            'clientsChart' => $clientsChart,
            'sentChart' => $this->fillSevenDaySeries($sentByDay),
            'failedChart' => $this->fillSevenDaySeries($failedByDay),
            'throttledChart' => $this->fillSevenDaySeries($throttledByDay),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, Stat>
     */
    private function statsFromPayload(array $payload): array
    {
        $trialClients = (int) $payload['trialClients'];
        $newTenantsLast7Days = (int) $payload['newTenantsLast7Days'];
        $trialPart = $trialClients > 0 ? "На пробном: {$trialClients}" : 'Без пробных';
        $newPart = $newTenantsLast7Days > 0 ? "Новых за 7 дн.: {$newTenantsLast7Days}" : 'Новых за 7 дн.: 0';

        return [
            Stat::make('Активных клиентов', (string) $payload['activeClients'])
                ->description("{$trialPart} · {$newPart}")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->chart($payload['clientsChart'])
                ->color('success'),

            Stat::make('Отправлено писем', (string) $payload['sentMails'])
                ->description('tenant_mail_logs, статус «отправлено»')
                ->chart($payload['sentChart'])
                ->color('primary'),

            Stat::make('Ошибки доставки', (string) $payload['errorMails'])
                ->description('tenant_mail_logs, статус «ошибка»')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->chart($payload['failedChart'])
                ->color('danger'),

            Stat::make('Throttled (лимит)', (string) $payload['throttledRows'])
                ->description('Записей с throttled_count > 0')
                ->chart($payload['throttledChart'])
                ->color('warning'),
        ];
    }

    private function chartWindowStart(): Carbon
    {
        return Carbon::today()->subDays(6)->startOfDay();
    }

    private function chartWindowEndInclusive(): Carbon
    {
        return Carbon::today()->endOfDay();
    }

    /**
     * @return Collection<string, int> date Y-m-d => count
     */
    private function tenantCreationsByDay(): Collection
    {
        $start = $this->chartWindowStart();
        $end = $this->chartWindowEndInclusive();
        $dateExpr = $this->sqlDateColumn('created_at');

        return $this->groupCountByDateExpr(
            Tenant::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end),
            $dateExpr,
        );
    }

    /**
     * @return Collection<string, int>
     */
    private function mailLogsByEffectiveDay(Builder $base, string $dateExprSql): Collection
    {
        $start = $this->chartWindowStart();
        $end = $this->chartWindowEndInclusive();

        return $this->groupCountByDateExpr(
            $base
                ->whereRaw("{$dateExprSql} >= ? AND {$dateExprSql} <= ?", [
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d'),
                ]),
            $dateExprSql,
        );
    }

    /**
     * @return Collection<string, int>
     */
    private function mailLogsByDayColumn(Builder $base, string $column): Collection
    {
        $start = $this->chartWindowStart();
        $end = $this->chartWindowEndInclusive();
        $dateExpr = $this->sqlDateColumn($column);

        return $this->groupCountByDateExpr(
            $base
                ->where($column, '>=', $start)
                ->where($column, '<=', $end),
            $dateExpr,
        );
    }

    /**
     * @return Collection<string, int> keys Y-m-d
     */
    private function groupCountByDateExpr(Builder $query, string $dateExprSql): Collection
    {
        $rows = $query
            ->clone()
            ->selectRaw("{$dateExprSql} as stat_day, COUNT(*) as stat_cnt")
            ->groupByRaw($dateExprSql)
            ->get();

        return $rows->mapWithKeys(function ($row): array {
            $day = $row->stat_day;
            $key = $day instanceof Carbon
                ? $day->format('Y-m-d')
                : Carbon::parse((string) $day)->format('Y-m-d');

            return [$key => (int) $row->stat_cnt];
        });
    }

    /**
     * @param  Collection<string, int>  $countsByDate
     * @return list<int>
     */
    private function fillSevenDaySeries(Collection $countsByDate): array
    {
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $key = Carbon::today()->subDays($i)->format('Y-m-d');
            $out[] = (int) ($countsByDate[$key] ?? 0);
        }

        return $out;
    }

    private function sqlDateColumn(string $column): string
    {
        return match ($this->driverName()) {
            'sqlite' => "date({$column})",
            'pgsql' => "({$column})::date",
            default => "DATE({$column})",
        };
    }

    /**
     * День для графика «отправлено»: дата sent_at, иначе created_at (как в прежних whereDate).
     */
    private function mailEffectiveDateSql(string $kind): string
    {
        $a = match ($kind) {
            'sent' => 'sent_at',
            'failed' => 'failed_at',
            default => 'created_at',
        };

        $coalesced = "COALESCE({$a}, created_at)";

        return match ($this->driverName()) {
            'sqlite' => "date({$coalesced})",
            'pgsql' => "({$coalesced})::date",
            default => "DATE({$coalesced})",
        };
    }

    private function driverName(): string
    {
        return (string) Tenant::query()->getConnection()->getDriverName();
    }
}
