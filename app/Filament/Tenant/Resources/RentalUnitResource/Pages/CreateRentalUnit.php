<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalUnitResource\Pages;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Tenant\Resources\RentalUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRentalUnit extends CreateRecord
{
    protected static string $resource = RentalUnitResource::class;

    /** @var list<int>|null */
    protected ?array $pendingTenantLocationIds = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('tenant_location_ids', $data)) {
            $raw = $data['tenant_location_ids'] ?? [];
            $this->pendingTenantLocationIds = array_values(array_filter(
                array_map('intval', is_array($raw) ? $raw : []),
                fn (int $id): bool => $id > 0,
            ));
        }
        unset($data['tenant_location_ids']);

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        if ($this->pendingTenantLocationIds === null) {
            return;
        }

        $record = $this->record->fresh(['motorcycle']);
        $motorcycle = $record?->motorcycle;
        if (
            $record !== null
            && $motorcycle !== null
            && $motorcycle->uses_fleet_units
            && ($motorcycle->location_mode ?? null) === MotorcycleLocationMode::PerUnit
        ) {
            $record->tenantLocations()->sync($this->pendingTenantLocationIds);
        }
        $this->pendingTenantLocationIds = null;
    }
}
