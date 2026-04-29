<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewResource\Pages;

use App\Filament\Tenant\Resources\ReviewImportSourceResource;
use App\Filament\Tenant\Resources\ReviewResource;
use App\Filament\Tenant\Resources\ReviewResource\Support\InteractsWithReviewSectionTabs;
use App\Jobs\Reviews\FetchReviewImportPreview;
use App\Models\ReviewImportSource;
use App\Models\User;
use App\Services\Reviews\Imports\ReviewImportPreviewService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Страница зарегистрирована на {@see ReviewResource} (nested URL); модель записи — {@see ReviewImportSource}. */
final class EditReviewImportSource extends EditRecord
{
    use InteractsWithReviewSectionTabs;

    protected static string $resource = ReviewResource::class;

    protected static ?string $title = 'Источник импорта';

    protected function reviewSectionActiveTab(): string
    {
        return 'sources';
    }

    public function getModel(): string
    {
        return ReviewImportSource::class;
    }

    protected function resolveRecord(int | string $key): Model
    {
        $record = ReviewImportSource::query()
            ->where('tenant_id', (int) (currentTenant()->id ?? 0))
            ->whereKey($key)
            ->first();

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(ReviewImportSource::class, [(string) $key]);
        }

        return $record;
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can('update', $this->getRecord()), 403);
    }

    protected function getAllRelationManagers(): array
    {
        return ReviewImportSourceResource::getRelations();
    }

    public function form(Schema $schema): Schema
    {
        return ReviewImportSourceResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchPreview')
                ->label('Подгрузить превью')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->disabled(fn (): bool => ! $this->record->isPreviewSupported())
                ->tooltip(fn (): ?string => $this->record->isPreviewSupported()
                    ? null
                    : 'Для этого провайдера нет официальной загрузки текстов; используйте ручной CSV.')
                ->requiresConfirmation()
                ->action(function (): void {
                    FetchReviewImportPreview::dispatch($this->record->id);
                    Notification::make()
                        ->title('Загрузка поставлена в очередь')
                        ->success()
                        ->send();
                }),
            Action::make('importManualCsv')
                ->label('Загрузить CSV')
                ->icon('heroicon-o-document-text')
                ->visible(fn (): bool => $this->record->provider === 'manual'
                    || $this->record->provider === 'two_gis'
                    || $this->record->provider === 'yandex_maps')
                ->form([
                    Textarea::make('csv')
                        ->label('CSV (первая строка — заголовки)')
                        ->rows(10)
                        ->required()
                        ->helperText('Колонки: author_name, body, rating, reviewed_at, author_avatar_url, source_url'),
                ])
                ->action(function (array $data): void {
                    $rows = self::parseManualCsv((string) ($data['csv'] ?? ''));
                    $n = app(ReviewImportPreviewService::class)->ingestManualRows($this->record, $rows);
                    Notification::make()
                        ->title('Добавлено кандидатов: '.$n)
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->successRedirectUrl(fn (): string => ListReviewImportSources::getUrl()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function parseManualCsv(string $raw): array
    {
        $lines = preg_split('/\R/u', trim($raw)) ?: [];
        if ($lines === []) {
            return [];
        }
        $headerLine = array_shift($lines);
        $headers = str_getcsv((string) $headerLine);
        $headers = array_map(fn ($h) => Str::slug(trim((string) $h), '_'), $headers);
        $out = [];
        foreach ($lines as $line) {
            if (trim((string) $line) === '') {
                continue;
            }
            $cells = str_getcsv((string) $line);
            $row = [];
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $cells[$i] ?? null;
            }
            if (isset($row['body']) && trim((string) $row['body']) !== '') {
                $out[] = $row;
            }
        }

        return $out;
    }
}
