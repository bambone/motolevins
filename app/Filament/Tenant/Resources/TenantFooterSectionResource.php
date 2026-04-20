<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\TenantFooterSectionResource\Pages;
use App\Filament\Tenant\Resources\TenantFooterSectionResource\RelationManagers\TenantFooterLinksRelationManager;
use App\Models\TenantFooterSection;
use App\Tenant\Footer\FooterSectionType;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class TenantFooterSectionResource extends Resource
{
    protected static ?string $model = TenantFooterSection::class;

    protected static ?string $navigationLabel = 'Подвал сайта';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 42;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $modelLabel = 'Секция подвала';

    protected static ?string $pluralModelLabel = 'Подвал сайта';

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
        $types = [];
        foreach (FooterSectionType::all() as $t) {
            $types[$t] = FooterSectionType::label($t);
        }

        return $schema
            ->columns(1)
            ->components([
                Section::make('Секция')
                    ->schema([
                        Select::make('type')
                            ->label('Тип')
                            ->options($types)
                            ->required()
                            ->native(true),
                        TextInput::make('section_key')
                            ->label('Ключ (для сидов/миграций)')
                            ->maxLength(64)
                            ->helperText('Необязательно. Уникален в рамках клиента.'),
                        TextInput::make('title')
                            ->label('Заголовок (опционально)')
                            ->maxLength(255),
                        Textarea::make('body')
                            ->label('Текст (опционально)')
                            ->rows(3)
                            ->maxLength(2000),
                        Textarea::make('meta_json')
                            ->label('Поля типа (JSON)')
                            ->rows(12)
                            ->required()
                            ->default('{}')
                            ->helperText('Контракт полей зависит от типа (см. документацию продукта). Для link_groups ссылки задаются ниже на вкладке «Ссылки секции».'),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_enabled')
                            ->label('Включено')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => FooterSectionType::label($state)),
                TextColumn::make('section_key')->label('Ключ')->placeholder('—'),
                IconColumn::make('is_enabled')->label('Вкл')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TenantFooterLinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantFooterSections::route('/'),
            'create' => Pages\CreateTenantFooterSection::route('/create'),
            'edit' => Pages\EditTenantFooterSection::route('/{record}/edit'),
        ];
    }
}
