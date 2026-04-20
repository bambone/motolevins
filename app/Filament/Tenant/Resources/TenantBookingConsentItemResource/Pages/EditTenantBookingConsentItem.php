<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantBookingConsentItemResource\Pages;

use App\Filament\Tenant\Resources\TenantBookingConsentItemResource;
use Filament\Resources\Pages\EditRecord;

class EditTenantBookingConsentItem extends EditRecord
{
    protected static string $resource = TenantBookingConsentItemResource::class;
}
