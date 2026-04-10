<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns;

use App\Enums\MotorcycleLocationMode;

trait PersistsMotorcycleTenantLocationsOnCreate
{
    /** @var list<int> */
    protected array $pendingTenantLocationIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $data['tenant_location_ids'] ?? [];
        $this->pendingTenantLocationIds = is_array($raw)
            ? array_values(array_unique(array_map('intval', array_filter($raw, fn ($v) => $v !== null && $v !== ''))))
            : [];
        unset($data['tenant_location_ids']);

        $data['uses_fleet_units'] = (bool) ($data['uses_fleet_units'] ?? false);
        $mode = $data['location_mode'] ?? MotorcycleLocationMode::Everywhere->value;
        if (! $data['uses_fleet_units'] && $mode === MotorcycleLocationMode::PerUnit->value) {
            $data['location_mode'] = MotorcycleLocationMode::Everywhere->value;
        }

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        $m = $this->getRecord();
        if ($m->location_mode === MotorcycleLocationMode::Selected) {
            $m->tenantLocations()->sync($this->pendingTenantLocationIds);
        } else {
            $m->tenantLocations()->sync([]);
        }

        parent::afterCreate();
    }
}
