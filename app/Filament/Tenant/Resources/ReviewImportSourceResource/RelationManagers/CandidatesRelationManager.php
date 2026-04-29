<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ReviewImportSourceResource\RelationManagers;

use App\Models\ReviewImportCandidate;
use App\Reviews\Import\ReviewImportCandidateStatus;
use App\Services\Reviews\Imports\ReviewCandidateImportService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CandidatesRelationManager extends RelationManager
{
    protected static string $relationship = 'candidates';

    protected static ?string $title = 'Кандидаты';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('author_name')->label('Автор')->placeholder('—'),
                TextColumn::make('rating')->label('Оценка')->placeholder('—'),
                TextColumn::make('body')->label('Текст')->limit(40),
                TextColumn::make('status')->badge(),
                TextColumn::make('imported_review_id')->label('Review id')->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100])
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
}
