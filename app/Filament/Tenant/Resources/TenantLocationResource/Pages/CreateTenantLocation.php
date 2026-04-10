<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantLocationResource\Pages;

use App\Filament\Tenant\Resources\TenantLocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantLocation extends CreateRecord
{
    protected static string $resource = TenantLocationResource::class;
}
