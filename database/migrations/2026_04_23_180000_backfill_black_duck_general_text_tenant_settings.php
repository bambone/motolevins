<?php

declare(strict_types=1);

use App\Models\TenantSetting;
use App\Tenant\BlackDuck\BlackDuckContentConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Название сайта, краткое описание и подпись в подвале хранятся в {@see tenant_settings} (ключи general.*).
 * Backfill только для пустых значений тенантов Black Duck (после ручного ввода не перезаписываем).
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('tenants')
            ->where('theme_key', BlackDuckContentConstants::THEME_KEY)
            ->pluck('id');

        foreach ($ids as $tid) {
            $tenantId = (int) $tid;
            $this->setIfBlank($tenantId, 'general.site_name', BlackDuckContentConstants::PUBLIC_SITE_NAME);
            $this->setIfBlank($tenantId, 'general.short_description', BlackDuckContentConstants::PUBLIC_SHORT_DESCRIPTION);
            $this->setIfBlank($tenantId, 'general.footer_tagline', BlackDuckContentConstants::PUBLIC_FOOTER_TAGLINE);
        }
    }

    public function down(): void
    {
        // Не откатываем: значения могли совпасть с ручным вводом.
    }

    private function setIfBlank(int $tenantId, string $key, string $value): void
    {
        $cur = trim((string) TenantSetting::getForTenant($tenantId, $key, ''));
        if ($cur !== '') {
            return;
        }
        TenantSetting::setForTenant($tenantId, $key, $value, 'string');
    }
};
