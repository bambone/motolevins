<?php

namespace App\Filament\Platform\Resources;

use App\Filament\Platform\Resources\Concerns\GrantsPlatformPanelAccess;
use App\Filament\Platform\Resources\PlatformBookableServiceResource\Pages;
use App\Filament\Shared\Lifecycle\AdminFilamentDelete;
use App\Filament\Support\AdminEmptyState;
use App\Models\BookableService;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PlatformBookableServiceResource extends Resource
{
    use GrantsPlatformPanelAccess;

    protected static ?string $model = BookableService::class;

    protected static ?string $navigationLabel = 'Услуги (platform scheduling)';

    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $pluralModelLabel = 'Услуги';

    protected static ?string $panel = 'platform';

    protected static string|UnitEnum|null $navigationGroup = 'Платформа';

    protected static ?int $navigationSort = 25;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('scheduling_scope', SchedulingScope::Platform)
            ->whereNull('tenant_id');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->description('Как услуга называется и что видит клиент на странице записи.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->label('Адрес в URL (slug)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Короткий идентификатор в ссылке. Уникален в пределах платформы, латиница и дефисы.'),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->helperText('Необязательно: кратко, что входит в услугу.'),
                    ]),
                Section::make('Длительность и сетка времени')
                    ->description('Сколько длится приём и как часто система предлагает варианты начала.')
                    ->schema([
                        TextInput::make('duration_minutes')
                            ->label('Длительность приёма')
                            ->suffix('мин')
                            ->numeric()
                            ->minValue(1)
                            ->default(60)
                            ->required()
                            ->helperText('Чистое время услуги для клиента, без перерывов до и после.'),
                        TextInput::make('slot_step_minutes')
                            ->label('Шаг между слотами')
                            ->suffix('мин')
                            ->numeric()
                            ->minValue(5)
                            ->default(15)
                            ->required()
                            ->helperText('Интервал между возможными временами начала. Например, 15 — 10:00, 10:15, 10:30…. Не меньше 5 минут.'),
                    ]),
                Section::make('Перерывы вокруг приёма')
                    ->description('Дополнительные минуты до и после, чтобы в календаре не стояли встречи вплотную.')
                    ->schema([
                        TextInput::make('buffer_before_minutes')
                            ->label('Запас до начала')
                            ->suffix('мин')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Время на подготовку перед тем, как клиент приходит или начинается услуга.'),
                        TextInput::make('buffer_after_minutes')
                            ->label('Запас после окончания')
                            ->suffix('мин')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Время после приёма, пока слот считается занятым (уборка, переход к следующему клиенту).'),
                    ]),
                Section::make('Правила онлайн-записи')
                    ->description('Ограничения по времени: как рано и как поздно клиент может выбрать слот.')
                    ->schema([
                        TextInput::make('min_booking_notice_minutes')
                            ->label('Минимум времени до начала слота')
                            ->suffix('мин')
                            ->numeric()
                            ->minValue(0)
                            ->default(120)
                            ->required()
                            ->helperText('Сколько минут должно пройти от момента записи до начала выбранного слота. Пример: 120 — нельзя записаться меньше чем за 2 часа; 0 — можно взять ближайшее свободное время.'),
                        TextInput::make('max_booking_horizon_days')
                            ->label('Запись не дальше, чем')
                            ->suffix('дн.')
                            ->numeric()
                            ->minValue(1)
                            ->default(60)
                            ->required()
                            ->helperText('Клиент не увидит слотов позже этой границы от сегодняшней даты.'),
                    ]),
                Section::make('Статус и порядок в списке')
                    ->description('Подтверждение вручную — заявка создаётся, но визит нужно утвердить в кабинете. Неактивная услуга не показывается при записи.')
                    ->schema([
                        Toggle::make('requires_confirmation')
                            ->label('Подтверждать заявку вручную')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Показывать при онлайн-записи')
                            ->default(true),
                        TextInput::make('sort_weight')
                            ->label('Порядок в списке')
                            ->numeric()
                            ->default(0)
                            ->helperText('Меньшее число — выше на публичной странице, при одинаковом значении порядок может быть любым.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return AdminEmptyState::applyInitial(
            $table
                ->columns([
                    TextColumn::make('title')->label('Название')->searchable(),
                    TextColumn::make('slug')->label('URL-идентификатор'),
                    IconColumn::make('is_active')->label('Активна')->boolean(),
                ])
                ->actions([EditAction::make()])
                ->bulkActions([
                    BulkActionGroup::make([
                        AdminFilamentDelete::makeBulkDeleteAction(),
                    ]),
                ]),
            'Шаблонов услуг записи пока нет',
            'Создайте эталонную услугу для клиентов платформы — её можно использовать как основу при настройке сайтов.',
            'heroicon-o-calendar-days',
            [CreateAction::make()->label('Создать услугу')],
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformBookableServices::route('/'),
            'create' => Pages\CreatePlatformBookableService::route('/create'),
            'edit' => Pages\EditPlatformBookableService::route('/{record}/edit'),
        ];
    }
}
