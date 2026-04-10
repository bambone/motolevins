<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantLocationResource\Pages;

use App\Filament\Tenant\Resources\TenantLocationResource;
use Filament\Resources\Pages\EditRecord;

class EditTenantLocation extends EditRecord
{
    protected static string $resource = TenantLocationResource::class;
}
