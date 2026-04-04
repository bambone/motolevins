<?php

namespace Database\Seeders;

use App\Models\Integration;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'motolevins')->first();

        if ($tenant === null) {
            return;
        }

        Integration::firstOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'rentprog'],
            [
                'name' => 'RentProg',
                'is_enabled' => false,
                'config' => [
                    'api_key' => '',
                    'base_url' => '',
                ],
            ]
        );
    }
}
