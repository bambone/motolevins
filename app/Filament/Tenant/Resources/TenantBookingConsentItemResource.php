<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\TenantBookingConsentItemResource\Pages;
use App\Models\TenantBookingConsentItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class TenantBookingConsentItemResource extends Resource
{
    protected static ?string $model = TenantBookingConsentItem::class;

    protected static ?string $navigationLabel = 'Согласия бронирования';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 28;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Пункт согласия';

    protected static ?string $pluralModelLabel = 'Согласия бронирования';

    public static function canAccess(): bool
    {
        return Gate::allows('manage_settings');
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = \currentTenant();

        return parent::getEloquentQuery()
            ->when($tenant !== null, fn (Builder $q) => $q->where('tenant_id', $tenant->id));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Текст и ссылка')
                    ->schema([
                        TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->maxLength(64)
                            ->alphaDash()
                            ->helperText('Уникален в рамках клиента (латиница, цифры, дефис).'),
                        TextInput::make('label')
                            ->label('Текст для посетителя')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('link_text')
                            ->label('Текст ссылки')
                            ->maxLength(255),
                        TextInput::make('link_url')
                            ->label('URL ссылки')
                            ->url()
                            ->maxLength(2048),
                        Toggle::make('is_required')
                            ->label('Обязательное')
                            ->default(true),
                        Toggle::make('is_enabled')
                            ->label('Включено')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Код')->searchable(),
                TextColumn::make('label')->label('Текст')->wrap(),
                IconColumn::make('is_required')->label('Обяз.')->boolean(),
                IconColumn::make('is_enabled')->label('Вкл')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantBookingConsentItems::route('/'),
            'create' => Pages\CreateTenantBookingConsentItem::route('/create'),
            'edit' => Pages\EditTenantBookingConsentItem::route('/{record}/edit'),
        ];
    }
}
