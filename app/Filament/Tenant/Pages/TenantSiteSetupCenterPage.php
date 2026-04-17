<?php

namespace App\Filament\Tenant\Pages;

use App\Models\TenantSetupItemState;
use App\TenantSiteSetup\SetupItemRegistry;
use App\TenantSiteSetup\SetupProgressService;
use App\TenantSiteSetup\SetupSessionService;
use App\TenantSiteSetup\SetupValueSnapshotResolver;
use App\TenantSiteSetup\TenantSiteSetupFeature;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class TenantSiteSetupCenterPage extends Page
{
    protected static ?string $navigationLabel = 'Настройка сайта';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $title = 'Центр настройки сайта';

    protected static ?string $slug = 'site-setup';

    protected string $view = 'filament.tenant.pages.tenant-site-setup-center';

    public static function canAccess(): bool
    {
        if (! TenantSiteSetupFeature::enabled()) {
            return false;
        }

        return Gate::allows('manage_settings') && currentTenant() !== null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startGuided')
                ->label(fn (): string => $this->hasPausedSession ? 'Продолжить мастер' : 'Запустить мастер')
                ->icon('heroicon-o-play')
                ->action(function (): void {
                    $tenant = currentTenant();
                    $user = Auth::user();
                    if ($tenant === null || $user === null) {
                        return;
                    }
                    app(SetupSessionService::class)->startOrResume($tenant, $user);
                    $this->redirect(static::getUrl());
                })
                ->visible(fn (): bool => TenantSiteSetupFeature::enabled()),
            Action::make('startFreshGuided')
                ->label('Новая очередь')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Начать мастер заново?')
                ->modalDescription('Текущая сессия на паузе будет сброшена, очередь шагов пересчитается с нуля.')
                ->action(function (): void {
                    $tenant = currentTenant();
                    $user = Auth::user();
                    if ($tenant === null || $user === null) {
                        return;
                    }
                    app(SetupSessionService::class)->startFreshGuidedSession($tenant, $user);
                    $this->redirect(static::getUrl());
                })
                ->visible(fn (): bool => TenantSiteSetupFeature::enabled() && $this->hasPausedSession),
        ];
    }

    public function getHasPausedSessionProperty(): bool
    {
        $tenant = currentTenant();
        $user = Auth::user();
        if ($tenant === null || $user === null) {
            return false;
        }

        return app(SetupSessionService::class)->pausedSession($tenant, $user) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return [];
        }

        return app(SetupProgressService::class)->summary($tenant);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCategoryRowsProperty(): array
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return [];
        }

        $defs = SetupItemRegistry::definitions();
        $snap = app(SetupValueSnapshotResolver::class);
        $rows = [];
        foreach ($defs as $key => $def) {
            $state = TenantSetupItemState::query()
                ->where('tenant_id', $tenant->id)
                ->where('item_key', $key)
                ->first();
            $exec = $state?->current_status ?? 'pending';
            $executionLabel = match ($exec) {
                'snoozed' => 'Отложено',
                'not_needed' => 'Не требуется',
                'completed' => 'Выполнено',
                default => 'Ожидание',
            };
            $rows[] = [
                'key' => $key,
                'category' => $def->categoryKey,
                'title' => $def->title,
                'snapshot' => $snap->snapshot($tenant, $def),
                'execution_status' => $exec,
                'execution_label' => $executionLabel,
                'can_restore' => in_array($exec, ['snoozed', 'not_needed'], true),
            ];
        }

        return $rows;
    }

}
