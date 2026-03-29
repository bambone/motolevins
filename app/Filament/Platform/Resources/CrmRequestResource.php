<?php

namespace App\Filament\Platform\Resources;

use App\Filament\Platform\Resources\Concerns\GrantsPlatformPanelAccess;
use App\Filament\Platform\Resources\CrmRequestResource\Pages;
use App\Filament\Shared\CRM\CrmSharedFilters;
use App\Filament\Shared\CRM\CrmSharedInfolist;
use App\Filament\Shared\CRM\CrmSharedTable;
use App\Models\CrmRequest;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CrmRequestResource extends Resource
{
    use GrantsPlatformPanelAccess;

    protected static ?string $model = CrmRequest::class;

    protected static ?string $navigationLabel = 'CRM-заявки';

    protected static ?string $modelLabel = 'CRM-заявка';

    protected static ?string $pluralModelLabel = 'CRM-заявки';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $panel = 'platform';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('tenant_id');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CrmSharedInfolist::schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(CrmSharedTable::columns())
            ->filters(CrmSharedFilters::tableFilters(static::getEloquentQuery()))
            ->defaultSort('id', 'desc')
            ->recordAction(ViewAction::class)
            ->recordUrl(null)
            ->actions([
                ViewAction::make()->slideOver(),
                EditAction::make()
                    ->slideOver()
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(CrmRequest::statusLabels())
                            ->required(),
                    ])
                    ->modalWidth('md'),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmRequests::route('/'),
        ];
    }
}
