<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->string('catalog_scenario', 120)->nullable()->after('short_description');
            $table->string('catalog_highlight_1', 48)->nullable()->after('catalog_scenario');
            $table->string('catalog_highlight_2', 48)->nullable()->after('catalog_highlight_1');
            $table->string('catalog_highlight_3', 48)->nullable()->after('catalog_highlight_2');
            $table->string('catalog_price_note', 80)->nullable()->after('catalog_highlight_3');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn([
                'catalog_scenario',
                'catalog_highlight_1',
                'catalog_highlight_2',
                'catalog_highlight_3',
                'catalog_price_note',
            ]);
        });
    }
};
