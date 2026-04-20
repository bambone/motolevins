<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantFooterSectionResource\Pages;

use App\Filament\Tenant\Resources\TenantFooterSectionResource;
use App\Tenant\Footer\FooterSectionMetaValidator;
use App\Tenant\Footer\TenantFooterSectionQuotaValidator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTenantFooterSection extends CreateRecord
{
    protected static string $resource = TenantFooterSectionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = \currentTenant();
        if ($tenant === null) {
            throw ValidationException::withMessages(['type' => 'Нет контекста клиента.']);
        }

        $raw = $data['meta_json'] ?? null;
        if (is_string($raw)) {
            $meta = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $meta = $raw;
        } else {
            $meta = null;
        }
        if (! is_array($meta)) {
            throw ValidationException::withMessages(['meta_json' => 'Укажите корректный JSON для полей типа.']);
        }

        $type = (string) ($data['type'] ?? '');
        $validator = app(FooterSectionMetaValidator::class);
        $v = $validator->validate($type, $meta);
        if (! $v['ok']) {
            throw ValidationException::withMessages(['meta_json' => $v['message'] ?? 'Ошибка']);
        }
        if (! $validator->hasMinimumContentForEnabled($type, $v['meta'])) {
            throw ValidationException::withMessages(['meta_json' => 'Недостаточно содержимого для включённой секции.']);
        }

        app(TenantFooterSectionQuotaValidator::class)->validateForSave(
            (int) $tenant->id,
            $type,
            (bool) ($data['is_enabled'] ?? true),
            null,
        );

        $data['tenant_id'] = (int) $tenant->id;
        $data['meta_json'] = $v['meta'];

        return $data;
    }
}
