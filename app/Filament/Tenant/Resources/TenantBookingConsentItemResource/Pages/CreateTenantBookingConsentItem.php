<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantBookingConsentItemResource\Pages;

use App\Filament\Tenant\Resources\TenantBookingConsentItemResource;
use App\Models\TenantBookingConsentItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTenantBookingConsentItem extends CreateRecord
{
    protected static string $resource = TenantBookingConsentItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = \currentTenant();
        if ($tenant === null) {
            throw ValidationException::withMessages(['code' => 'Нет контекста клиента.']);
        }
        $data['tenant_id'] = (int) $tenant->id;

        if (TenantBookingConsentItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', (string) ($data['code'] ?? ''))
            ->exists()) {
            throw ValidationException::withMessages(['code' => 'Такой код уже используется.']);
        }

        return $data;
    }
}
