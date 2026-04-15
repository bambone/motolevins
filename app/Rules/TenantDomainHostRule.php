<?php

declare(strict_types=1);

namespace App\Rules;

use App\Filament\Tenant\Resources\CustomDomainResource;
use App\Models\TenantDomain;
use App\Services\Tenancy\TenantDomainHostRules;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

final class TenantDomainHostRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): void
    {
        $this->data = isset($data['data']) && is_array($data['data'])
            ? $data['data']
            : $data;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $livewire = Livewire::current();

        $tenantId = (int) ($this->data['tenant_id'] ?? 0);
        $type = (string) ($this->data['type'] ?? '');
        $ignoreId = null;

        if ($livewire instanceof EditRecord) {
            $record = $livewire->getRecord();
            if ($record instanceof TenantDomain) {
                $ignoreId = (int) $record->getKey();
                $tenantId = (int) $record->tenant_id;
                $type = (string) $record->type;
            }
        } elseif ($livewire instanceof CreateRecord && $livewire::getResource() === CustomDomainResource::class) {
            $tenantId = (int) (currentTenant()?->id ?? 0);
            $type = TenantDomain::TYPE_CUSTOM;
        }

        if ($tenantId <= 0) {
            $fail('Сначала выберите клиента.');

            return;
        }

        if ($type === '') {
            $fail('Укажите тип подключения домена.');

            return;
        }

        $service = app(TenantDomainHostRules::class);

        try {
            $canonical = $service->assertValidHostFormat((string) $value, $type);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $fail($message);
                }
            }

            return;
        }

        try {
            $service->assertAttachableOrThrow($canonical, $tenantId, $ignoreId, $type);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $fail($message);
                }
            }
        }
    }
}
