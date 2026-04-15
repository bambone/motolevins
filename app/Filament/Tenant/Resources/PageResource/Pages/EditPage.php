<?php

namespace App\Filament\Tenant\Resources\PageResource\Pages;

use App\Filament\Shared\Lifecycle\AdminFilamentDelete;
use App\Filament\Tenant\Resources\PageResource;
use App\Services\Tenancy\TenantPagePrimaryHtmlSync;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected ?string $pendingPrimaryHtml = null;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Контент и настройки';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPublic')
                ->label('Открыть на сайте')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(function (): string {
                    $record = $this->getRecord();
                    if ($record->status !== 'published') {
                        return '#';
                    }

                    return $record->slug === 'home' ? url('/') : url('/'.ltrim($record->slug, '/'));
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->getRecord()->status === 'published'),
            AdminFilamentDelete::configureTableDeleteAction(
                DeleteAction::make()
                    ->label('Удалить страницу')
                    ->modalHeading('Удалить страницу?')
                    ->modalDescription('Страница будет удалена вместе с блоками и SEO-данными.')
                    ->visible(fn (): bool => $this->getRecord()->slug !== 'home'),
                ['entry' => 'filament.tenant.page.edit'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($record->slug !== 'home') {
            $main = $record->sections()->where('section_key', 'main')->first();
            $data['primary_html'] = is_array($main?->data_json) ? ($main->data_json['content'] ?? '') : '';
        } else {
            $data['primary_html'] = '';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingPrimaryHtml = $data['primary_html'] ?? null;
        unset($data['primary_html']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(TenantPagePrimaryHtmlSync::class)->sync($this->getRecord(), $this->pendingPrimaryHtml);
        $this->pendingPrimaryHtml = null;
    }
}
