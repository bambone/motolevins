<?php

namespace App\Filament\Tenant\Resources\MotorcycleResource\Pages;

use App\Filament\Tenant\Resources\MotorcycleResource;
use App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns\PersistsMotorcycleTenantLocationsOnCreate;
use App\Filament\Tenant\Resources\MotorcycleResource\Pages\Concerns\SanitizesTipTapFullDescriptionBeforeValidate;
use Filament\Resources\Pages\CreateRecord;

class CreateMotorcycle extends CreateRecord
{
    use PersistsMotorcycleTenantLocationsOnCreate;
    use SanitizesTipTapFullDescriptionBeforeValidate;

    protected static string $resource = MotorcycleResource::class;
}
