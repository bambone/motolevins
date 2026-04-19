<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;

trait PersistsMotorcycleTenantLocationsOnCreate
{
    /** @var list<int> */
    protected array $pendingTenantLocationIds = [];

    /**
     * Извлечь локации карточки в {@see $pendingTenantLocationIds} и убрать из payload модели.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripTenantLocationsForCreate(array $data): array
    {
        $data = MotorcycleFormFieldKit::normalizeFleetLocationFormState($data);

        $raw = $data['tenant_location_ids'] ?? [];
        $this->pendingTenantLocationIds = is_array($raw)
            ? array_values(array_unique(array_map('intval', array_filter($raw, fn ($v) => $v !== null && $v !== ''))))
            : [];
        unset($data['tenant_location_ids']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->stripTenantLocationsForCreate($data);

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
