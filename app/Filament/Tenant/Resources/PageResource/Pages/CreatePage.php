<?php

namespace App\Filament\Tenant\Resources\PageResource\Pages;

use App\Filament\Tenant\Resources\PageResource;
use App\Services\Tenancy\TenantPagePrimaryHtmlSync;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected ?string $pendingPrimaryHtml = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingPrimaryHtml = $data['primary_html'] ?? null;
        unset($data['primary_html']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $page = $this->getRecord();
        app(TenantPagePrimaryHtmlSync::class)->sync($page, $this->pendingPrimaryHtml);
        $this->pendingPrimaryHtml = null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('edit', ['record' => $this->getRecord()]);
    }
}
