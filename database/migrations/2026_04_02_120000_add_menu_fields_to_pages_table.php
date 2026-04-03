<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_in_main_menu')->default(false)->after('published_at');
            $table->unsignedInteger('main_menu_sort_order')->default(0)->after('show_in_main_menu');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['show_in_main_menu', 'main_menu_sort_order']);
        });
    }
};
