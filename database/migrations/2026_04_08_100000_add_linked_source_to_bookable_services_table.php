<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookable_services', function (Blueprint $table) {
            if (! Schema::hasColumn('bookable_services', 'motorcycle_id')) {
                $table->foreignId('motorcycle_id')->nullable()->after('tenant_id')->constrained('motorcycles')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookable_services', 'rental_unit_id')) {
                $table->foreignId('rental_unit_id')->nullable()->after('motorcycle_id')->constrained('rental_units')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookable_services', 'sync_title_from_source')) {
                $table->boolean('sync_title_from_source')->default(true)->after('sort_weight');
            }
        });

        $this->safeIndex('bookable_services', ['tenant_id', 'scheduling_scope', 'motorcycle_id'], 'bookable_services_tenant_scope_moto_idx');
        $this->safeIndex('bookable_services', ['tenant_id', 'scheduling_scope', 'rental_unit_id'], 'bookable_services_tenant_scope_unit_idx');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('bookable_services', 'bookable_services_tenant_scope_moto_idx');
        $this->dropIndexIfExists('bookable_services', 'bookable_services_tenant_scope_unit_idx');

        Schema::table('bookable_services', function (Blueprint $table) {
            if (Schema::hasColumn('bookable_services', 'motorcycle_id')) {
                $table->dropForeign(['motorcycle_id']);
            }
            if (Schema::hasColumn('bookable_services', 'rental_unit_id')) {
                $table->dropForeign(['rental_unit_id']);
            }
        });

        Schema::table('bookable_services', function (Blueprint $table) {
            if (Schema::hasColumn('bookable_services', 'sync_title_from_source')) {
                $table->dropColumn('sync_title_from_source');
            }
            if (Schema::hasColumn('bookable_services', 'rental_unit_id')) {
                $table->dropColumn('rental_unit_id');
            }
            if (Schema::hasColumn('bookable_services', 'motorcycle_id')) {
                $table->dropColumn('motorcycle_id');
            }
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function safeIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (
                ! str_contains($msg, 'Duplicate key name')
                && ! str_contains($msg, 'already exists')
                && ! str_contains($msg, 'SQLSTATE[HY000]')
            ) {
                throw $e;
            }
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        } catch (Throwable) {
            // index missing
        }
    }
};
