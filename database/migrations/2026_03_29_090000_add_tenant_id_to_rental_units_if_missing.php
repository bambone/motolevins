<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * rental_units создаётся в 2026_03_19_150000, а колонка tenant_id добавлялась в 2026_03_19_100100
 * только если таблица уже существовала — при типичном порядке миграций таблицы ещё нет,
 * поэтому на проде часто остаётся rental_units без tenant_id (500 в tenant Filament).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_units')) {
            return;
        }

        if (! Schema::hasColumn('rental_units', 'tenant_id')) {
            Schema::table('rental_units', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('rental_units', 'tenant_id') && Schema::hasTable('motorcycles')
            && Schema::hasColumn('motorcycles', 'tenant_id')) {
            $pairs = DB::table('rental_units as ru')
                ->join('motorcycles as m', 'm.id', '=', 'ru.motorcycle_id')
                ->whereNull('ru.tenant_id')
                ->whereNotNull('m.tenant_id')
                ->select(['ru.id as rental_unit_id', 'm.tenant_id as tenant_id'])
                ->get();

            foreach ($pairs as $row) {
                DB::table('rental_units')
                    ->where('id', $row->rental_unit_id)
                    ->update(['tenant_id' => $row->tenant_id]);
            }
        }

        if (Schema::hasColumn('rental_units', 'tenant_id')) {
            $fallbackTenantId = DB::table('tenants')->where('slug', 'motolevins')->value('id')
                ?? DB::table('tenants')->orderBy('id')->value('id');

            if ($fallbackTenantId) {
                DB::table('rental_units')->whereNull('tenant_id')->update(['tenant_id' => $fallbackTenantId]);
            }
        }

        if (Schema::hasColumn('rental_units', 'tenant_id')
            && ! DB::table('rental_units')->whereNull('tenant_id')->exists()) {
            Schema::table('rental_units', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental_units') || ! Schema::hasColumn('rental_units', 'tenant_id')) {
            return;
        }

        Schema::table('rental_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
