<?php

use Database\Seeders\Tenant\AflyatunovExpertBootstrap;
use Illuminate\Database\Migrations\Migration;

/**
 * Повторная заливка бандла обложек программ и обновление cover_* (после смены WebP в репозитории или если раньше не выполнилась 2026_04_12_140000).
 */
return new class extends Migration
{
    public function up(): void
    {
        AflyatunovExpertBootstrap::syncProgramCoverAssetsToTenantPublicDisk();
    }

    public function down(): void
    {
        // Объекты в bucket не удаляем.
    }
};
