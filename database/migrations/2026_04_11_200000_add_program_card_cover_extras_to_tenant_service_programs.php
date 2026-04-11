<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_service_programs')) {
            return;
        }
        // cover_image_ref добавляется миграцией 2026_04_12_120000 (идёт позже по имени файла).
        $afterMobile = Schema::hasColumn('tenant_service_programs', 'cover_image_ref')
            ? 'cover_image_ref'
            : 'outcomes_json';

        Schema::table('tenant_service_programs', function (Blueprint $table) use ($afterMobile): void {
            if (! Schema::hasColumn('tenant_service_programs', 'cover_mobile_ref')) {
                $table->string('cover_mobile_ref', 2048)->nullable()->after($afterMobile);
            }
            if (! Schema::hasColumn('tenant_service_programs', 'cover_image_alt')) {
                $table->string('cover_image_alt', 512)->nullable()->after('cover_mobile_ref');
            }
            if (! Schema::hasColumn('tenant_service_programs', 'cover_object_position')) {
                $table->string('cover_object_position', 64)->nullable()->after('cover_image_alt');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_service_programs')) {
            return;
        }
        Schema::table('tenant_service_programs', function (Blueprint $table): void {
            if (Schema::hasColumn('tenant_service_programs', 'cover_object_position')) {
                $table->dropColumn('cover_object_position');
            }
            if (Schema::hasColumn('tenant_service_programs', 'cover_image_alt')) {
                $table->dropColumn('cover_image_alt');
            }
            if (Schema::hasColumn('tenant_service_programs', 'cover_mobile_ref')) {
                $table->dropColumn('cover_mobile_ref');
            }
        });
    }
};
