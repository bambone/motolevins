<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ReviewImportSourceResource\Pages;
use App\Filament\Tenant\Resources\ReviewImportSourceResource\RelationManagers\CandidatesRelationManager;
use App\Models\ReviewImportSource;
use App\Reviews\Import\ReviewImportSourceStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ReviewImportSourceResource extends Resource
{
    protected static ?string $model = ReviewImportSource::class;

    protected static ?string $navigationLabel = 'Источники отзывов';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 19;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $modelLabel = 'Источник отзывов';

    protected static ?string $pluralModelLabel = 'Источники отзывов';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Источник')
                    ->schema([
                        Select::make('provider')
                            ->label('Провайдер')
                            ->options([
                                'auto' => 'Авто по ссылке',
                                'manual' => 'Ручной (CSV/JSON)',
                                'vk_topic' => 'VK — обсуждение (topic)',
                                'vk_wall' => 'VK — пост (wall)',
                                'two_gis' => '2ГИС (без авто-текстов API)',
                                'yandex_maps' => 'Яндекс Карты (нужен официальный канал)',
                            ])
                            ->required()
                            ->native(true),
                        TextInput::make('title')
                            ->label('Название')
                            ->maxLength(255),
                        TextInput::make('source_url')
                            ->label('Ссылка на источник')
                            ->required()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->placeholder('—')->searchable(),
                TextColumn::make('provider')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_synced_at')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextColumn::make('candidates_count')->counts('candidates')->label('Кандидаты'),
                TextColumn::make('last_error_code')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Источников пока нет')
            ->emptyStateDescription('Добавьте ссылку на VK или ручной источник; для 2ГИС и Яндекса доступен только ручной импорт текстов.')
            ->emptyStateIcon('heroicon-o-arrow-down-tray');
    }

    public static function getRelations(): array
    {
        return [
            CandidatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewImportSources::route('/'),
            'create' => Pages\CreateReviewImportSource::route('/create'),
            'edit' => Pages\EditReviewImportSource::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            ReviewImportSourceStatus::DRAFT => 'Черновик',
            ReviewImportSourceStatus::READY => 'Готов',
            ReviewImportSourceStatus::NEEDS_AUTH => 'Нужна авторизация',
            ReviewImportSourceStatus::UNSUPPORTED => 'Нет API для текстов',
            ReviewImportSourceStatus::FAILED => 'Ошибка',
            ReviewImportSourceStatus::DISABLED => 'Отключён',
        ];
    }
}
