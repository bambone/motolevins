<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gated drop: set MOTORCYCLE_LEGACY_PRICE_COLUMNS_DROP=true only after backfill, acceptance gate, and cutover.
 * Run migrate again with the env set so {@see up()} executes the column removal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! filter_var(env('MOTORCYCLE_LEGACY_PRICE_COLUMNS_DROP', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        if (! Schema::hasTable('motorcycles')) {
            return;
        }

        Schema::table('motorcycles', function (Blueprint $table): void {
            $drop = [];
            foreach (['price_per_day', 'price_2_3_days', 'price_week', 'catalog_price_note'] as $col) {
                if (Schema::hasColumn('motorcycles', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    public function down(): void
    {
        // Irreversible data migration; restore from backup if needed.
    }
};
