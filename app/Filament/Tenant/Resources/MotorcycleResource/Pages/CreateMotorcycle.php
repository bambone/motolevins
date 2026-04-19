<?php

namespace App\Filament\Tenant\Resources\MotorcycleResource\Pages;

use App\Filament\Tenant\Resources\MotorcycleResource;
use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns\PersistsMotorcycleTenantLocationsOnCreate;
use App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns\SanitizesTipTapFullDescriptionBeforeValidate;
use Filament\Resources\Pages\CreateRecord;

class CreateMotorcycle extends CreateRecord
{
    use PersistsMotorcycleTenantLocationsOnCreate;
    use SanitizesTipTapFullDescriptionBeforeValidate;

    protected static string $resource = MotorcycleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->stripTenantLocationsForCreate($data);

        if (isset($data['pricing_tariffs'])) {
            return MotorcycleFormFieldKit::mergePricingProfileIntoMotorcycleData($data);
        }

        return $data;
    }
}
