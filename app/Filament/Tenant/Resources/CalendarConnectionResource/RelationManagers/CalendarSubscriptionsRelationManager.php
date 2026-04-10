<?php

namespace App\Filament\Tenant\Resources\CalendarConnectionResource\RelationManagers;

use App\Models\CalendarSubscription;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CalendarSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Календари в аккаунте';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Подписка')
                    ->schema([
                        TextInput::make('external_calendar_id')->label('Внешний ID календаря')->required()->maxLength(255),
                        TextInput::make('title')->label('Заголовок')->maxLength(255),
                        TextInput::make('timezone')->label('Часовой пояс')->maxLength(64),
                        Toggle::make('use_for_busy')->label('Учитывать busy')->default(true),
                        Toggle::make('use_for_write')->label('Писать события')->default(false),
                        Toggle::make('is_active')->label('Активно')->default(true),
                        TextInput::make('stale_after_seconds')
                            ->label('Устаревание, сек')
                            ->numeric()
                            ->minValue(60)
                            ->nullable(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('external_calendar_id')->label('ID')->limit(24),
                TextColumn::make('title')->label('Название')->placeholder('—'),
                IconColumn::make('use_for_busy')->label('Busy')->boolean(),
                IconColumn::make('use_for_write')->label('Write')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        $data['calendar_connection_id'] = (int) $this->getOwnerRecord()->getKey();

                        return CalendarSubscription::query()->create($data);
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
