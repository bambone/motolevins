<?php

namespace Database\Seeders;

use Database\Seeders\Tenant\DementievAdvocateBootstrap;
use Illuminate\Database\Seeder;

/**
 * Алиас для {@see DementievAdvocateBootstrap} (историческое имя в скриптах).
 *
 * Запуск: {@code php artisan db:seed --class=DementievAdvocateBootstrapSeeder}
 */
class DementievAdvocateBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        (new DementievAdvocateBootstrap)->run();
    }
}
