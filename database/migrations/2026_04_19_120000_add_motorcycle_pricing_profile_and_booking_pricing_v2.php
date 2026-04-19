<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            if (! Schema::hasColumn('motorcycles', 'pricing_profile_json')) {
                $table->json('pricing_profile_json')->nullable()->after('catalog_price_note');
            }
            if (! Schema::hasColumn('motorcycles', 'pricing_profile_schema_version')) {
                $table->unsignedSmallInteger('pricing_profile_schema_version')->default(1)->after('pricing_profile_json');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'pricing_snapshot_schema_version')) {
                $table->unsignedSmallInteger('pricing_snapshot_schema_version')->nullable()->after('pricing_snapshot_json');
            }
            if (! Schema::hasColumn('bookings', 'currency')) {
                $table->char('currency', 3)->nullable()->after('pricing_snapshot_schema_version');
            }
            if (! Schema::hasColumn('bookings', 'rental_total_minor')) {
                $table->unsignedBigInteger('rental_total_minor')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('bookings', 'deposit_amount_minor')) {
                $table->unsignedBigInteger('deposit_amount_minor')->nullable()->after('rental_total_minor');
            }
            if (! Schema::hasColumn('bookings', 'payable_now_minor')) {
                $table->unsignedBigInteger('payable_now_minor')->nullable()->after('deposit_amount_minor');
            }
            if (! Schema::hasColumn('bookings', 'selected_tariff_id')) {
                $table->string('selected_tariff_id', 64)->nullable()->after('payable_now_minor');
            }
            if (! Schema::hasColumn('bookings', 'selected_tariff_kind')) {
                $table->string('selected_tariff_kind', 32)->nullable()->after('selected_tariff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            if (Schema::hasColumn('motorcycles', 'pricing_profile_schema_version')) {
                $table->dropColumn('pricing_profile_schema_version');
            }
            if (Schema::hasColumn('motorcycles', 'pricing_profile_json')) {
                $table->dropColumn('pricing_profile_json');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            foreach ([
                'selected_tariff_kind',
                'selected_tariff_id',
                'payable_now_minor',
                'deposit_amount_minor',
                'rental_total_minor',
                'currency',
                'pricing_snapshot_schema_version',
            ] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
