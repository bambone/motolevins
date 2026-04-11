<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Обложки заливались через {@see TenantStorage::putInArea(PublicSite)} в {@code site/expert_auto/...},
 * а в БД ошибочно сохранялись ключи {@code tenants/{id}/public/expert_auto/...} без сегмента {@code site/}.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_service_programs')) {
            return;
        }

        foreach (['cover_image_ref', 'cover_mobile_ref'] as $column) {
            if (! Schema::hasColumn('tenant_service_programs', $column)) {
                continue;
            }

            $expr = "REPLACE({$column}, '/public/expert_auto/', '/public/site/expert_auto/')";

            DB::table('tenant_service_programs')
                ->whereNotNull($column)
                ->where($column, 'like', '%/public/expert_auto/%')
                ->where($column, 'not like', '%/public/site/expert_auto/%')
                ->update([$column => DB::raw($expr)]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_service_programs')) {
            return;
        }

        foreach (['cover_image_ref', 'cover_mobile_ref'] as $column) {
            if (! Schema::hasColumn('tenant_service_programs', $column)) {
                continue;
            }

            $expr = "REPLACE({$column}, '/public/site/expert_auto/', '/public/expert_auto/')";

            DB::table('tenant_service_programs')
                ->whereNotNull($column)
                ->where($column, 'like', '%/public/site/expert_auto/%')
                ->update([$column => DB::raw($expr)]);
        }
    }
};
