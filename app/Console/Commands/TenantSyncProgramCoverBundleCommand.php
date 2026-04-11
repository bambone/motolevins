<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesTenantArgument;
use App\Support\Storage\TenantStorage;
use App\Tenant\Expert\ExpertAutoProgramCoverInstaller;
use App\Tenant\Expert\ExpertAutoProgramCoverRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Копирует WebP пресеты из {@code tenants/_system/themes/expert_auto/program-covers/} в публичный диск тенанта.
 */
final class TenantSyncProgramCoverBundleCommand extends Command
{
    use ResolvesTenantArgument;

    protected $signature = 'tenant:sync-program-cover-bundle
                            {tenant=aflyatunov : slug или id тенанта}
                            {--purge : Удалить все объекты под site/expert_auto/programs/ на публичном диске тенанта}
                            {--clear-refs : Вместе с --purge: обнулить cover_image_ref и cover_mobile_ref у программ тенанта}';

    protected $description = 'Скопировать пресеты обложек expert_auto из системного пула R2 в site/expert_auto/programs/… и обновить cover_* в БД';

    public function handle(): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('tenant'));

        if (($tenant->theme_key ?? '') !== ExpertAutoProgramCoverRegistry::THEME_KEY) {
            $this->error('Команда только для тенантов с темой expert_auto (текущая: '.($tenant->theme_key ?? '—').').');

            return self::FAILURE;
        }

        if ($this->option('clear-refs') && ! $this->option('purge')) {
            $this->error('Опция --clear-refs допустима только вместе с --purge.');

            return self::FAILURE;
        }

        if ($this->option('purge')) {
            $n = TenantStorage::forTrusted((int) $tenant->id)->deleteAllPublicFilesUnderSitePath('expert_auto/programs');
            $this->info("Удалено объектов в site/expert_auto/programs/: {$n}");
        }

        if ($this->option('clear-refs')) {
            if (! Schema::hasTable('tenant_service_programs')) {
                $this->warn('Таблица tenant_service_programs отсутствует.');

                return self::FAILURE;
            }
            $u = ['updated_at' => now()];
            if (Schema::hasColumn('tenant_service_programs', 'cover_image_ref')) {
                $u['cover_image_ref'] = null;
            }
            if (Schema::hasColumn('tenant_service_programs', 'cover_mobile_ref')) {
                $u['cover_mobile_ref'] = null;
            }
            DB::table('tenant_service_programs')->where('tenant_id', $tenant->id)->update($u);
            $this->info('Поля cover_image_ref / cover_mobile_ref обнулены для программ тенанта.');
        }

        app(ExpertAutoProgramCoverInstaller::class)->installFromSystemBundledPool((int) $tenant->id);

        $disk = config('tenant_storage.public_disk', 'public');
        $this->info("Синхронизация обложек для «{$tenant->slug}» завершена (public disk: {$disk}). Источник: при EXPERT_AUTO_COVERS_FROM_BRAND — кадры из site/brand/* (hero, portrait, …), иначе tenants/_system/.../program-covers/. Смените TENANT_STORAGE_PUBLIC_URL_VERSION после перезаливки под тем же ключом. Уникальные фото по программам — загрузите WebP в resources/themes/expert_auto/public/program-covers/ и theme:push-system-bundled.");

        return self::SUCCESS;
    }
}
