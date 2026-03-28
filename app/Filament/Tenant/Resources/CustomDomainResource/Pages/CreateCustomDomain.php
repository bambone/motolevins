<?php

namespace App\Filament\Tenant\Resources\CustomDomainResource\Pages;

use App\Filament\Tenant\Resources\CustomDomainResource;
use App\Services\Tenancy\TenantDomainService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomDomain extends CreateRecord
{
    protected static string $resource = CustomDomainResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = currentTenant();
        if (! $tenant) {
            throw new \RuntimeException('Нет контекста клиента.');
        }

        return app(TenantDomainService::class)->addCustomDomain($tenant, (string) ($data['host'] ?? ''));
    }
}
