<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->text('detail_audience')->nullable()->after('catalog_price_note');
            $table->json('detail_use_case_bullets')->nullable()->after('detail_audience');
            $table->json('detail_advantage_bullets')->nullable()->after('detail_use_case_bullets');
            $table->text('detail_rental_notes')->nullable()->after('detail_advantage_bullets');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn([
                'detail_audience',
                'detail_use_case_bullets',
                'detail_advantage_bullets',
                'detail_rental_notes',
            ]);
        });
    }
};
