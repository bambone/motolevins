<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MotorcycleResource\Form;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Forms\Components\SeoMetaFields;
use App\Filament\Forms\Components\TenantSpatieMediaLibraryFileUpload;
use App\Models\Motorcycle;
use App\Models\TenantLocation;
use App\Services\Seo\TenantSeoPublicPreviewService;
use App\Support\CatalogHighlightNormalizer;
use App\Support\Motorcycle\MotorcycleMediaPersistence;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Переиспользуемые «кирпичи» полей мотоцикла для Create (resource form) и Livewire block editors на Edit.
 */
final class MotorcycleFormFieldKit
{
    /**
     * @return array<int, Component>
     */
    public static function mainInfoFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Название')
                ->id('motorcycle-name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                    if ($operation === 'create' && $state) {
                        $set('slug', Str::slug($state));
                    }
                }),
            TextInput::make('slug')
                ->label('URL-идентификатор')
                ->id('motorcycle-slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Адрес карточки в каталоге, например /catalog/your-slug. Латиница, цифры и дефис.'),
            TextInput::make('brand')
                ->label('Бренд')
                ->id('motorcycle-brand')
                ->maxLength(255),
            TextInput::make('model')
                ->label('Модель')
                ->id('motorcycle-model')
                ->maxLength(255),
            Textarea::make('short_description')
                ->label('Позиционирование в каталоге')
                ->id('motorcycle-short-description')
                ->rows(3)
                ->helperText('1–2 короткие строки: для какого сценария модель и чем отличается от соседних. Только правдивый маркетинговый смысл, без выдуманных цифр.')
                ->columnSpanFull(),
            TextInput::make('catalog_scenario')
                ->label('Сценарий / кому подойдёт')
                ->id('motorcycle-catalog-scenario')
                ->maxLength(120)
                ->placeholder('Например: Туристу и трассе')
                ->columnSpanFull(),
            Fieldset::make('Быстрые преимущества (чипы в каталоге, словарь на сайте)')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('catalog_highlight_1')
                                ->label('Чип 1')
                                ->id('motorcycle-catalog-highlight-1')
                                ->placeholder('—')
                                ->formatStateUsing(fn (?string $state): ?string => CatalogHighlightNormalizer::normalizeToKey($state))
                                ->options(fn (): array => CatalogHighlightNormalizer::selectOptions()),
                            Select::make('catalog_highlight_2')
                                ->label('Чип 2')
                                ->id('motorcycle-catalog-highlight-2')
                                ->placeholder('—')
                                ->formatStateUsing(fn (?string $state): ?string => CatalogHighlightNormalizer::normalizeToKey($state))
                                ->options(fn (): array => CatalogHighlightNormalizer::selectOptions()),
                            Select::make('catalog_highlight_3')
                                ->label('Чип 3')
                                ->id('motorcycle-catalog-highlight-3')
                                ->placeholder('—')
                                ->formatStateUsing(fn (?string $state): ?string => CatalogHighlightNormalizer::normalizeToKey($state))
                                ->options(fn (): array => CatalogHighlightNormalizer::selectOptions()),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function pageModelFields(): array
    {
        return [
            Textarea::make('detail_audience')
                ->label('Кому подойдёт')
                ->id('motorcycle-detail-audience')
                ->rows(3)
                ->helperText('1–3 предложения. Если пусто — на сайте используется сценарий из поля выше.')
                ->columnSpanFull(),
            Textarea::make('detail_use_case_bullets')
                ->label('Сценарий: тезисы (по одному на строку, до 4)')
                ->id('motorcycle-detail-use-case')
                ->rows(5)
                ->formatStateUsing(function ($state): string {
                    if (is_array($state)) {
                        return implode("\n", array_filter($state, 'filled'));
                    }

                    return '';
                })
                ->dehydrateStateUsing(function (?string $state): array {
                    if ($state === null || trim($state) === '') {
                        return [];
                    }
                    $lines = preg_split('/\r\n|\r|\n/', $state) ?: [];
                    $lines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));

                    return array_slice($lines, 0, 4);
                })
                ->columnSpanFull(),
            Textarea::make('detail_advantage_bullets')
                ->label('Ключевые плюсы (по одному на строку, до 6)')
                ->id('motorcycle-detail-advantages')
                ->rows(6)
                ->formatStateUsing(function ($state): string {
                    if (is_array($state)) {
                        return implode("\n", array_filter($state, 'filled'));
                    }

                    return '';
                })
                ->dehydrateStateUsing(function (?string $state): array {
                    if ($state === null || trim($state) === '') {
                        return [];
                    }
                    $lines = preg_split('/\r\n|\r|\n/', $state) ?: [];
                    $lines = array_values(array_filter(array_map('trim', $lines), fn (string $l): bool => $l !== ''));

                    return array_slice($lines, 0, 6);
                })
                ->columnSpanFull(),
            Textarea::make('detail_rental_notes')
                ->label('Аренда: примечания к этой модели')
                ->id('motorcycle-detail-rental')
                ->rows(4)
                ->helperText('Только проверяемые формулировки. Общие условия — на странице «Правила аренды».')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function specsSections(): array
    {
        return [
            Section::make('Базовые параметры')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('engine_cc')
                                ->label('Объём двигателя')
                                ->id('motorcycle-engine-cc')
                                ->numeric()
                                ->suffix('см³'),
                            TextInput::make('power')
                                ->label('Мощность')
                                ->id('motorcycle-power')
                                ->numeric()
                                ->suffix('л.с.'),
                            TextInput::make('transmission')
                                ->label('Трансмиссия')
                                ->id('motorcycle-transmission')
                                ->maxLength(255),
                            TextInput::make('year')
                                ->label('Год выпуска')
                                ->id('motorcycle-year')
                                ->numeric(),
                            TextInput::make('mileage')
                                ->label('Пробег')
                                ->id('motorcycle-mileage')
                                ->numeric()
                                ->suffix('км'),
                        ]),
                ])
                ->columns(1)
                ->compact()
                ->secondary(),
            Section::make('Дополнительные характеристики (расширенный режим)')
                ->description('Пары «название — значение» для редких полей. Основные поля выше предпочтительнее. Не используйте без необходимости — опечатки в ключах не попадут на сайт автоматически.')
                ->schema([
                    KeyValue::make('specs_json')
                        ->label('Произвольные параметры')
                        ->id('motorcycle-specs-json')
                        ->keyLabel('Название')
                        ->valueLabel('Значение')
                        ->reorderable(),
                ])
                ->columns(1)
                ->compact()
                ->secondary()
                ->collapsed()
                ->collapsible(),
        ];
    }

    /**
     * @return array<int, RichEditor>
     */
    public static function fullDescriptionField(): array
    {
        return [
            RichEditor::make('full_description')
                ->label('Описание')
                ->id('motorcycle-full-description')
                ->columnSpanFull(),
        ];
    }

    public static function seoSnippetPreviewPlaceholder(): Placeholder
    {
        return Placeholder::make('seo_resolver_preview')
            ->label('Публичный title / description (как у TenantSeoResolver)')
            ->content(function (?Motorcycle $record): HtmlString {
                if ($record === null || ! $record->exists) {
                    return new HtmlString('<p class="text-sm text-gray-500">Сохраните запись, чтобы увидеть предпросмотр.</p>');
                }
                $tenant = tenant();
                if ($tenant === null) {
                    return new HtmlString('');
                }
                $snippet = app(TenantSeoPublicPreviewService::class)->motorcycleSnippet($tenant, $record->fresh(['seoMeta', 'category']));
                $t = e($snippet['title']);
                $d = e($snippet['description']);

                return new HtmlString(
                    '<div class="space-y-2 text-sm"><p><span class="font-medium text-gray-600 dark:text-gray-400">Title:</span> '.$t.'</p>'
                    .'<p><span class="font-medium text-gray-600 dark:text-gray-400">Description:</span> '.$d.'</p></div>'
                );
            })
            ->columnSpanFull();
    }

    /**
     * @return array<int, Component>
     */
    public static function publishingFields(): array
    {
        return [
            Select::make('status')
                ->label('Статус')
                ->id('motorcycle-status')
                ->options(Motorcycle::statuses())
                ->required()
                ->default('available'),
            TextInput::make('sort_order')
                ->label('Порядок сортировки')
                ->id('motorcycle-sort-order')
                ->numeric()
                ->default(0),
            Toggle::make('show_on_home')
                ->label('Показывать на главной')
                ->id('motorcycle-show-on-home')
                ->default(false),
            Toggle::make('show_in_catalog')
                ->label('Показывать в каталоге')
                ->id('motorcycle-show-in-catalog')
                ->default(true),
            Toggle::make('is_recommended')
                ->label('Рекомендуемый')
                ->id('motorcycle-is-recommended')
                ->default(false),
            Select::make('category_id')
                ->label('Категория')
                ->id('motorcycle-category')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('price_per_day')
                ->label('Цена за день')
                ->id('motorcycle-price-per-day')
                ->numeric()
                ->required()
                ->default(0)
                ->suffix('₽'),
            TextInput::make('price_2_3_days')
                ->label('2–3 дня')
                ->id('motorcycle-price-2-3-days')
                ->numeric()
                ->suffix('₽'),
            TextInput::make('price_week')
                ->label('Неделя')
                ->id('motorcycle-price-week')
                ->numeric()
                ->suffix('₽'),
            TextInput::make('catalog_price_note')
                ->label('Подпись под ценой в каталоге')
                ->id('motorcycle-catalog-price-note')
                ->maxLength(80)
                ->placeholder('Только реальное условие')
                ->helperText('Необязательно. Например: «за сутки», «бронь по предоплате» — только если это действительно так.')
                ->columnSpanFull(),
        ];
    }

    /**
     * Режим учёта (единицы парка) и доступность по локациям — для create и для Livewire-блока на edit.
     *
     * @return array<int, Component>
     */
    public static function fleetAndLocationCardFields(): array
    {
        return [
            Toggle::make('uses_fleet_units')
                ->label('Использовать единицы парка')
                ->helperText('Несколько физических экземпляров одной карточки. Строки единиц добавляются после сохранения карточки, на экране редактирования.')
                ->default(false)
                ->live(),
            Select::make('location_mode')
                ->label('Где доступен товар')
                ->options(function (Get $get): array {
                    $base = [
                        MotorcycleLocationMode::Everywhere->value => MotorcycleLocationMode::Everywhere->label(),
                        MotorcycleLocationMode::Selected->value => 'Только в выбранных локациях',
                    ];
                    if ($get('uses_fleet_units')) {
                        $base[MotorcycleLocationMode::PerUnit->value] = MotorcycleLocationMode::PerUnit->label();
                    }

                    return $base;
                })
                ->default(MotorcycleLocationMode::Everywhere->value)
                ->required()
                ->native(true)
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                    if ($state === MotorcycleLocationMode::PerUnit->value && ! $get('uses_fleet_units')) {
                        $set('location_mode', MotorcycleLocationMode::Everywhere->value);
                    }
                    if ($state !== MotorcycleLocationMode::Selected->value) {
                        $set('tenant_location_ids', []);
                    }
                }),
            CheckboxList::make('tenant_location_ids')
                ->label('Локации')
                ->options(function (): array {
                    return TenantLocation::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->visible(fn (Get $get): bool => $get('location_mode') === MotorcycleLocationMode::Selected->value)
                ->columns(2)
                ->required(fn (Get $get): bool => $get('location_mode') === MotorcycleLocationMode::Selected->value)
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if ($get('location_mode') !== MotorcycleLocationMode::Selected->value) {
                            return;
                        }
                        $ids = is_array($value) ? array_values(array_filter($value, fn ($v) => $v !== null && $v !== '')) : [];
                        if ($ids === []) {
                            $fail('Выберите хотя бы одну локацию.');
                        }
                    },
                ])
                ->dehydrated(fn (Get $get): bool => $get('location_mode') === MotorcycleLocationMode::Selected->value)
                ->helperText('При режиме «только в выбранных» нужно выбрать минимум одну локацию из справочника «Локации».'),
        ];
    }

    /**
     * @return array<int, TenantSpatieMediaLibraryFileUpload>
     */
    public static function mediaUploadFields(): array
    {
        return [
            TenantSpatieMediaLibraryFileUpload::make('cover')
                ->collection('cover')
                ->disk(config('media-library.disk_name'))
                ->visibility('public')
                ->conversionsDisk(config('media-library.disk_name'))
                ->image()
                ->label('Обложка')
                ->helperText('Основное изображение карточки. Рекомендуется 16:9. При редактировании файл сохраняется в медиатеку сразу после успешной загрузки. При создании новой карточки — после первого сохранения формы.')
                ->id('motorcycle-cover')
                ->columnSpanFull()
                ->fetchFileInformation(false)
                ->orientImagesFromExif(false)
                ->maxSize(15360)
                ->afterStateUpdated(MotorcycleMediaPersistence::persistAfterUploadStateChange(...)),
            TenantSpatieMediaLibraryFileUpload::make('gallery')
                ->collection('gallery')
                ->disk(config('media-library.disk_name'))
                ->visibility('public')
                ->conversionsDisk(config('media-library.disk_name'))
                ->image()
                ->multiple()
                ->maxFiles(10)
                ->reorderable()
                ->label('Галерея')
                ->helperText('Дополнительные изображения для слайдера. На экране редактирования новые файлы сохраняются сразу после загрузки.')
                ->id('motorcycle-gallery')
                ->columnSpanFull()
                ->fetchFileInformation(false)
                ->orientImagesFromExif(false)
                ->maxSize(15360)
                ->afterStateUpdated(MotorcycleMediaPersistence::persistAfterUploadStateChange(...)),
        ];
    }

    public static function seoMetaSection(): Section
    {
        return SeoMetaFields::make(useTabs: true);
    }
}
