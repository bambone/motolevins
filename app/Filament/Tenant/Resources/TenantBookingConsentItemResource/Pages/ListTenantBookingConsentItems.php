<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantBookingConsentItemResource\Pages;

use App\Filament\Tenant\Resources\TenantBookingConsentItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantBookingConsentItems extends ListRecords
{
    protected static string $resource = TenantBookingConsentItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
