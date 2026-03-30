<?php

use Database\Seeders\DomainTerminologySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('domain_localization_presets') || ! Schema::hasTable('domain_terms')) {
            return;
        }

        $now = now();

        $presets = [
            [
                'slug' => 'advanced_driving_pk',
                'name' => 'Курсы ПК / контраварийное вождение',
                'description' => 'Повышение квалификации, безопасное и контраварийное вождение, запись на программы и занятия.',
                'sort_order' => 48,
            ],
            [
                'slug' => 'nail_service_booking',
                'name' => 'Ногтевой сервис / запись',
                'description' => 'Маникюр, педикюр, запись к мастеру',
                'sort_order' => 52,
            ],
        ];

        foreach ($presets as $row) {
            $exists = DB::table('domain_localization_presets')->where('slug', $row['slug'])->exists();
            $payload = [
                'name' => $row['name'],
                'description' => $row['description'],
                'is_active' => true,
                'sort_order' => $row['sort_order'],
                'updated_at' => $now,
            ];
            if ($exists) {
                DB::table('domain_localization_presets')->where('slug', $row['slug'])->update($payload);
            } else {
                DB::table('domain_localization_presets')->insert(array_merge($payload, [
                    'slug' => $row['slug'],
                    'created_at' => $now,
                ]));
            }
        }

        DB::table('domain_localization_presets')->where('slug', 'moto_rental')->update([
            'name' => 'Прокат мотоциклов',
            'description' => 'Мотопрокат: бронирование, парк техники, CRM по заявкам.',
            'sort_order' => 20,
            'updated_at' => $now,
        ]);

        (new DomainTerminologySeeder)->run();
    }

    /**
     * Откат не выполняем: удаление пресетов ломает FK у tenants.domain_localization_preset_id
     * и теряет связи preset_terms. Откат схемы — через down() исходной миграции терминологии.
     */
    public function down(): void
    {
        //
    }
};
