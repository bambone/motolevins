<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewResource\Pages;

use App\Filament\Tenant\Resources\ReviewResource;
use App\Filament\Tenant\Resources\ReviewResource\Support\InteractsWithReviewSectionTabs;
use App\Models\ReviewImportCandidate;
use App\Models\ReviewImportSource;
use App\Models\User;
use App\Reviews\Import\ReviewImportCandidateStatus;
use App\Services\Reviews\Imports\ReviewCandidateImportService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Кандидаты импорта под {@see ReviewResource}; записи — {@see ReviewImportCandidate}. */
final class ListReviewImportCandidates extends ListRecords
{
    use InteractsWithReviewSectionTabs;

    protected static string $resource = ReviewResource::class;

    protected static ?string $title = 'Кандидаты импорта';

    protected function reviewSectionActiveTab(): string
    {
        return 'candidates';
    }

    public function getModel(): string
    {
        return ReviewImportCandidate::class;
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can('viewAny', ReviewImportCandidate::class), 403);
    }

    protected function getTableQuery(): Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        return ReviewImportCandidate::query()
            ->where('tenant_id', (int) (currentTenant()->id ?? 0))
            ->with(['source:id,title']);
    }

    protected function makeTable(): Table
    {
        $table = $this->makeBaseTable()
            ->query(fn (): Builder => $this->getTableQuery())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->modelLabel('кандидат')
            ->pluralModelLabel('кандидаты');

        return $this->configureCandidatesTable($table);
    }

    protected function configureCandidatesTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('source.title')->label('Источник')->placeholder('—')->sortable(),
                TextColumn::make('author_name')->label('Автор')->placeholder('—')->searchable(),
                TextColumn::make('rating')->label('Оценка')->placeholder('—'),
                TextColumn::make('body')->label('Текст')->limit(48),
                TextColumn::make('status')->badge(),
                TextColumn::make('imported_review_id')
                    ->label('Отзыв')
                    ->placeholder('—')
                    ->url(fn (ReviewImportCandidate $r): ?string => $r->imported_review_id !== null
                        ? EditReview::getUrl(['record' => $r->imported_review_id])
                        : null),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100])
            ->filters([
                SelectFilter::make('review_import_source_id')
                    ->label('Источник')
                    ->options(fn (): array => ReviewImportSource::query()
                        ->where('tenant_id', (int) (currentTenant()->id ?? 0))
                        ->orderBy('title')
                        ->get()
                        ->mapWithKeys(fn (ReviewImportSource $s): array => [
                            (string) $s->id => ($s->title !== null && $s->title !== '') ? $s->title : 'Источник #'.$s->id])
                        ->all()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('import_as_reviews')
                        ->label('Импортировать в отзывы')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->form([
                            Toggle::make('publish')
                                ->label('Опубликовать сразу')
                                ->default(false),
                            Select::make('rating')
                                ->label('Оценка при импорте')
                                ->options([
                                    '' => 'Как у кандидата / без оценки',
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                    '5' => '5',
                                ])
                                ->native(true),
                        ])
                        ->action(function (BulkAction $action, array $data): void {
                            $publish = (bool) ($data['publish'] ?? false);
                            $forced = isset($data['rating']) && $data['rating'] !== '' && $data['rating'] !== null
                                ? (int) $data['rating']
                                : null;
                            $records = $action->getSelectedRecords()->filter(
                                fn (ReviewImportCandidate $c): bool => $c->status !== ReviewImportCandidateStatus::IMPORTED,
                            );
                            if ($records->isEmpty()) {
                                Notification::make()->title('Нет строк для импорта')->warning()->send();

                                return;
                            }
                            $n = count(app(ReviewCandidateImportService::class)->importCandidates(
                                $records,
                                $publish,
                                $forced,
                            ));
                            Notification::make()->title('Импортировано отзывов: '.$n)->success()->send();
                        }),
                    BulkAction::make('ignore')
                        ->label('Игнорировать')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (BulkAction $action): void {
                            foreach ($action->getSelectedRecords() as $c) {
                                if (! $c instanceof ReviewImportCandidate) {
                                    continue;
                                }
                                if ($c->status === ReviewImportCandidateStatus::IMPORTED) {
                                    continue;
                                }
                                $c->update(['status' => ReviewImportCandidateStatus::IGNORED]);
                            }
                            Notification::make()->title('Отмечено как игнор')->success()->send();
                        }),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sources')
                ->label('К источникам')
                ->url(ListReviewImportSources::getUrl())
                ->color('gray'),
        ];
    }
}
