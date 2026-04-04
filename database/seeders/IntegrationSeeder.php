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

        // Bypass tenant global scope (seeding has no HTTP tenant). Use forceFill so tenant_id
        // is always persisted even if a deploy missed an updated $fillable on Integration.
        $query = Integration::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('type', 'rentprog');

        if ($query->exists()) {
            return;
        }

        $integration = new Integration;
        $integration->forceFill([
            'tenant_id' => $tenant->id,
            'type' => 'rentprog',
            'name' => 'RentProg',
            'is_enabled' => false,
            'config' => [
                'api_key' => '',
                'base_url' => '',
            ],
        ]);
        $integration->save();
    }
}
