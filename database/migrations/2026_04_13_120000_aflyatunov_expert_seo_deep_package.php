<?php

use Database\Seeders\Tenant\AflyatunovExpertBootstrap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO-пакет для tenant aflyatunov: мета главной и витринных страниц, JSON-LD, llms.txt, overrides маршрутов, H1 в hero.
 *
 * @see AflyatunovExpertBootstrap::applyAflyatunovExpertSeoPackage()
 */
return new class extends Migration
{
    public function up(): void
    {
        $tenantId = (int) DB::table('tenants')->where('slug', 'aflyatunov')->value('id');
        if ($tenantId < 1) {
            return;
        }

        AflyatunovExpertBootstrap::applyAflyatunovExpertSeoPackage($tenantId);
    }
};
