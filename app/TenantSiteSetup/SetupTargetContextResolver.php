<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Определяет, соответствует ли текущий запрос целевому контексту шага (маршрут и «можно честно продолжить отсюда»).
 */
final class SetupTargetContextResolver
{
    public function __construct(
        private readonly SetupItemUrlResolver $urls,
    ) {}

    /**
     * @return array{on_target_route: bool, can_complete_here: bool, target_url: ?string}
     */
    public function resolve(Tenant $tenant, SetupItemDefinition $def, Request $request): array
    {
        $targetUrl = $this->urls->urlFor($tenant, $def);
        $currentName = $request->route()?->getName();

        $onTarget = $this->matchesTargetRoute($tenant, $def, $request, $currentName);
        $canComplete = $this->canCompleteHere($tenant, $def, $request, $onTarget, $currentName);

        return [
            'on_target_route' => $onTarget,
            'can_complete_here' => $canComplete,
            'target_url' => $targetUrl,
        ];
    }

    private function matchesTargetRoute(Tenant $tenant, SetupItemDefinition $def, Request $request, ?string $currentName): bool
    {
        if ($currentName === null) {
            return false;
        }

        if ($def->key === 'programs.first_published_program') {
            return in_array($currentName, [
                'filament.admin.resources.tenant-service-programs.index',
                'filament.admin.resources.tenant-service-programs.create',
                'filament.admin.resources.tenant-service-programs.edit',
            ], true);
        }

        if ($this->isHomePageBuilderItem($def->key)) {
            if ($currentName !== 'filament.admin.resources.pages.edit') {
                return false;
            }

            return $this->isHomePageRecord($tenant, $request);
        }

        if ($def->filamentRouteName !== null && Route::has($def->filamentRouteName)) {
            return $currentName === $def->filamentRouteName;
        }

        return false;
    }

    private function canCompleteHere(
        Tenant $tenant,
        SetupItemDefinition $def,
        Request $request,
        bool $onTarget,
        ?string $currentName,
    ): bool {
        if (! $onTarget) {
            return false;
        }

        if ($def->key === 'programs.first_published_program') {
            return in_array($currentName, [
                'filament.admin.resources.tenant-service-programs.create',
                'filament.admin.resources.tenant-service-programs.edit',
            ], true);
        }

        if ($this->isHomePageBuilderItem($def->key)) {
            return $this->isHomePageRecord($tenant, $request);
        }

        return true;
    }

    private function isHomePageBuilderItem(string $key): bool
    {
        return $key === 'pages.home.hero_title'
            || $key === 'pages.home.hero_cta_or_contact_block';
    }

    private function isHomePageRecord(Tenant $tenant, Request $request): bool
    {
        $record = $request->route('record');
        if ($record instanceof Page) {
            return $record->slug === 'home'
                && (int) $record->tenant_id === (int) $tenant->id;
        }

        if (is_numeric($record)) {
            $page = Page::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey((int) $record)
                ->first();

            return $page !== null && $page->slug === 'home';
        }

        return false;
    }
}
