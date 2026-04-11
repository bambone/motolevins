<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ключ «auto» в UI платформы указывал на пустой tenant/themes/auto — приводим к рабочему default.
     */
    public function up(): void
    {
        DB::table('tenants')->where('theme_key', 'auto')->update([
            'theme_key' => 'default',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Не восстанавливаем «auto»: значение некорректно для рендера.
    }
};
