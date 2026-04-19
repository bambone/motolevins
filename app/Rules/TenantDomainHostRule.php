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
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

final class TenantDomainHostRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /** @var array<string, mixed> */
    protected array $allData = [];

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): void
    {
        $this->allData = $data;
        $this->data = isset($data['data']) && is_array($data['data'])
            ? $data['data']
            : $data;
    }

    /**
     * Filament table actions (slide-over edit) pass nested state to the validator; `tenant_id` may not
     * sit at the top level after a single `data` unwrap.
     */
    private function nestedFormValue(string $key): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        foreach (Arr::dot($this->allData) as $dottedKey => $value) {
            if ($dottedKey === $key || str_ends_with($dottedKey, '.'.$key)) {
                return $value;
            }
        }

        return null;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $livewire = Livewire::current();

        $tenantId = (int) ($this->nestedFormValue('tenant_id') ?? 0);
        $typeRaw = $this->nestedFormValue('type');
        $type = is_scalar($typeRaw) || $typeRaw === null ? (string) ($typeRaw ?? '') : '';
        $ignoreId = null;

        if ($livewire instanceof EditRecord) {
            $record = $livewire->getRecord();
            if ($record instanceof TenantDomain) {
                $ignoreId = (int) $record->getKey();
                $tenantId = (int) $record->tenant_id;
                if ($type === '') {
                    $type = (string) $record->type;
                }
            }
        } elseif ($livewire instanceof CreateRecord && $livewire::getResource() === CustomDomainResource::class) {
            $tenantId = (int) (currentTenant()?->id ?? 0);
            $type = TenantDomain::TYPE_CUSTOM;
        } elseif (is_object($livewire) && method_exists($livewire, 'getMountedAction')) {
            /** Table `EditAction` / slide-over: Livewire is `ListRecords`, not `EditRecord`. */
            $mounted = $livewire->getMountedAction();
            $record = $mounted?->getRecord();
            if ($record instanceof TenantDomain) {
                $ignoreId = (int) $record->getKey();
                if ($tenantId <= 0) {
                    $tenantId = (int) $record->tenant_id;
                }
                if ($type === '') {
                    $type = (string) $record->type;
                }
            }
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
