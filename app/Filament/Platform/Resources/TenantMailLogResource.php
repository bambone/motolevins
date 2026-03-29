<?php

namespace App\Filament\Platform\Resources;

use App\Filament\Platform\Resources\Concerns\GrantsPlatformPanelAccess;
use App\Filament\Platform\Resources\TenantMailLogResource\Pages;
use App\Models\TenantMailLog;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantMailLogResource extends Resource
{
    use GrantsPlatformPanelAccess;

    protected static ?string $model = TenantMailLog::class;

    protected static ?string $navigationLabel = 'Журнал почты';

    protected static ?string $modelLabel = 'Событие почты';

    protected static ?string $pluralModelLabel = 'Журнал почты';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Почта';

    protected static ?int $navigationSort = 2;

    protected static ?string $panel = 'platform';

    protected static ?string $recordTitleAttribute = 'correlation_id';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Событие')
                    ->schema([
                        TextEntry::make('correlation_id')->label('Correlation ID'),
                        TextEntry::make('tenant.name')->label('Клиент'),
                        TextEntry::make('to_email')->label('Кому'),
                        TextEntry::make('mailable_class')->label('Mailable'),
                        TextEntry::make('mail_type')->label('Тип'),
                        TextEntry::make('mail_group')->label('Группа'),
                        TextEntry::make('subject')->label('Тема'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (?string $state): string => $state ? (TenantMailLog::statusLabels()[$state] ?? $state) : '—'),
                        TextEntry::make('attempts')->label('Попытки'),
                        TextEntry::make('throttled_count')->label('Отложено лимитом (раз)'),
                        TextEntry::make('error_message')
                            ->label('Ошибка')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('queued_at')->label('Поставлено в очередь')->dateTime(),
                        TextEntry::make('started_at')->label('Начало отправки')->dateTime(),
                        TextEntry::make('sent_at')->label('Отправлено')->dateTime(),
                        TextEntry::make('failed_at')->label('Провал')->dateTime(),
                        TextEntry::make('created_at')->label('Создано')->dateTime(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('to_email')
                    ->label('Кому')
                    ->searchable(),
                TextColumn::make('mail_type')
                    ->label('Тип')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('mail_group')
                    ->label('Группа')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')
                    ->label('Тема')
                    ->limit(36)
                    ->tooltip(fn (TenantMailLog $record): ?string => $record->subject),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (TenantMailLog::statusLabels()[$state] ?? $state) : '—')
                    ->color(fn (?string $state): string => match ($state) {
                        TenantMailLog::STATUS_SENT => 'success',
                        TenantMailLog::STATUS_FAILED => 'danger',
                        TenantMailLog::STATUS_DEFERRED => 'warning',
                        TenantMailLog::STATUS_QUEUED => 'gray',
                        TenantMailLog::STATUS_PROCESSING => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('attempts')
                    ->label('Попытки')
                    ->sortable(),
                TextColumn::make('throttled_count')
                    ->label('Throttled')
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Ошибка')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(TenantMailLog::statusLabels()),
                SelectFilter::make('tenant_id')
                    ->label('Клиент')
                    ->relationship('tenant', 'name')
                    ->preload(),
                SelectFilter::make('mail_type')
                    ->label('Тип письма')
                    ->options(fn (): array => TenantMailLog::query()
                        ->whereNotNull('mail_type')
                        ->distinct()
                        ->orderBy('mail_type')
                        ->pluck('mail_type', 'mail_type')
                        ->all()),
                SelectFilter::make('mail_group')
                    ->label('Группа')
                    ->options(fn (): array => TenantMailLog::query()
                        ->whereNotNull('mail_group')
                        ->distinct()
                        ->orderBy('mail_group')
                        ->pluck('mail_group', 'mail_group')
                        ->all()),
                Filter::make('period')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        if (! empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }
                        if (! empty($data['until'])) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantMailLogs::route('/'),
            'view' => Pages\ViewTenantMailLog::route('/{record}'),
        ];
    }
}
