<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Forms\Components\TenantSpatieMediaLibraryFileUpload;
use App\Filament\Support\AdminEmptyState;
use App\Filament\Support\HintIconTooltip;
use App\Filament\Tenant\Resources\ReviewResource\Pages;
use App\Models\Page;
use App\Models\Review;
use App\Models\TenantServiceProgram;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ReviewResource extends Resource
{
    private const REVIEW_CATEGORY_KEY_MAX_LENGTH = 64;

    protected static ?string $model = Review::class;

    protected static ?string $navigationLabel = 'Отзывы';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        /** Показывать привязку отзыва к модели из каталога аренды (motorcycles) — только для мото-витрин. */
        $showMotorcycleCatalogLink = static fn (): bool => in_array(
            (string) (currentTenant()?->themeKey() ?? ''),
            ['moto', 'default'],
            true,
        );

        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        Section::make('Тексты и связи')
                            ->description('Эти поля попадают в блок «Отзывы» на страницах. Публикация управляется в колонке справа.')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Имя на сайте')
                                    ->required()
                                    ->maxLength(255)
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Как будет подписан автор на карточке отзыва.',
                                        'Имя или имя + контекст.',
                                    )),
                                TextInput::make('city')
                                    ->label('Город')
                                    ->maxLength(255)
                                    ->placeholder('Например, Челябинск')
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Необязательно.',
                                        'Показывается рядом с именем, если тема выводит город.',
                                    )),
                                TextInput::make('contact_email')
                                    ->label('Email отправителя')
                                    ->email()
                                    ->maxLength(255)
                                    ->helperText('Если отзыв пришёл с сайта и вы запросили email — не публикуется на витрине.')
                                    ->columnSpanFull(),
                                TextInput::make('headline')
                                    ->label('Заголовок / лид')
                                    ->maxLength(255)
                                    ->placeholder('Короткая тема отзыва')
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Одна строка над текстом: тема или эмоция («Контраварийка и зима»).',
                                        'На сайте может идти бейджем или подзаголовком.',
                                    )),
                                Select::make('category_key')
                                    ->label('Ключ темы')
                                    ->placeholder('Без привязки к теме')
                                    ->options(fn (): array => static::reviewCategoryKeySelectOptions())
                                    ->searchable()
                                    ->nullable()
                                    ->native(false)
                                    ->rules(['nullable', 'string', 'max:'.self::REVIEW_CATEGORY_KEY_MAX_LENGTH])
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(...static::reviewCategoryKeyTooltipLines())),
                                Textarea::make('body')
                                    ->label('Текст отзыва')
                                    ->rows(8)
                                    ->required()
                                    ->helperText('Краткая выдержка в карточке на сайте строится автоматически; полный текст в HTML — для SEO и кнопки «Читать полностью».')
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Обычный текст, переносы строк сохраняются; HTML из поля на сайт не передаётся как разметка.',
                                    ))
                                    ->columnSpanFull(),
                                Select::make('media_type')
                                    ->label('Тип контента')
                                    ->options(['text' => 'Только текст', 'video' => 'С видео'])
                                    ->default('text')
                                    ->live()
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        '«С видео» — укажите ниже ссылку.',
                                        'Для встроенного плеера: прямая ссылка на .mp4 / .webm или страница с плеером.',
                                    )),
                                TextInput::make('video_url')
                                    ->label('Ссылка на видео')
                                    ->url()
                                    ->required(fn (Get $get): bool => ($get('media_type') ?? 'text') === 'video')
                                    ->maxLength(2048)
                                    ->visible(fn (Get $get): bool => ($get('media_type') ?? 'text') === 'video')
                                    ->columnSpanFull()
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Обязательно, если выбран тип «С видео».',
                                        'Иначе поле можно не трогать.',
                                    )),
                                Select::make('motorcycle_id')
                                    ->label('Модель в каталоге аренды')
                                    ->relationship('motorcycle', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible($showMotorcycleCatalogLink)
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                        'Только для витрин с каталогом мотоциклов в прокат.',
                                        'Для детейлинга, expert и других тем поле скрыто.',
                                    )),
                            ])
                            ->columns(2)
                            ->columnSpan(['default' => 1, 'lg' => 8]),

                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 4])
                            ->extraAttributes([
                                'class' => 'lg:sticky lg:top-6 lg:z-10 lg:self-start',
                            ])
                            ->schema([
                                Section::make('Публикация и медиа')
                                    ->description('Компактно: статус, порядок, оценка, дата, аватар.')
                                    ->schema([
                                        Select::make('status')
                                            ->label('Статус публикации')
                                            ->options(Review::statuses())
                                            ->required()
                                            ->default('published')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'На сайте попадают только отзывы в статусе «Опубликован».',
                                                'Черновик и «Скрыт» — только в админке.',
                                            )),
                                        TextInput::make('sort_order')
                                            ->label('Порядок в списке')
                                            ->numeric()
                                            ->default(0)
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Меньшее число — выше в списке внутри своей группы.',
                                                'Избранные и обычные сортируются отдельно на сайте.',
                                            )),
                                        Toggle::make('is_featured')
                                            ->label('Крупная карточка (спотлайт)')
                                            ->default(false)
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Включите для 1–3 главных отзывов: крупный блок и бейдж на лендинге.',
                                                'Остальные — без этой отметки.',
                                            )),
                                        Select::make('rating')
                                            ->label('Оценка')
                                            ->options([
                                                '' => 'Не указана',
                                                '1' => '1',
                                                '2' => '2',
                                                '3' => '3',
                                                '4' => '4',
                                                '5' => '5',
                                            ])
                                            ->default('')
                                            ->native(true)
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Для импортов без звёзд оставьте «Не указана».',
                                                'На сайте звёзды не показываются, если оценка не задана.',
                                            )),
                                        DatePicker::make('date')
                                            ->label('Дата отзыва')
                                            ->native(false)
                                            ->displayFormat('d.m.Y')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Дата для сортировки и отображения («когда оставлен отзыв»).',
                                                'Можно поставить дату публикации.',
                                            )),
                                        TextInput::make('source')
                                            ->label('Источник (служебно)')
                                            ->maxLength(255)
                                            ->placeholder('site, yandex, …')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Метка для себя: откуда пришёл отзыв.',
                                                'На публичный сайт обычно не выводится.',
                                            )),
                                        TenantSpatieMediaLibraryFileUpload::make('avatar')
                                            ->collection('avatar')
                                            ->disk(config('media-library.disk_name'))
                                            ->visibility('public')
                                            ->conversionsDisk(config('media-library.disk_name'))
                                            ->image()
                                            ->imagePreviewHeight('64')
                                            ->avatar()
                                            ->label('Фото (аватар)')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Квадратное или портретное фото лица; отображение на сайте — до ~48×48 в карточке.',
                                                'Если задан URL ниже — файл важнее для вывода.',
                                            )),
                                        TextInput::make('meta_json.avatar_external_url')
                                            ->label('URL аватара (внешний)')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('https://…')
                                            ->hintIcon('heroicon-o-information-circle')
                                            ->hintIconTooltip(fn () => HintIconTooltip::lines(
                                                'Необязательно: лицо с публичного профиля (например Яндекс/2ГИС).',
                                                'На сайте подгружается лениво (loading=lazy), без обязательного файла в медиатеке.',
                                            )),
                                    ]),
                            ]),
                    ]),

                Section::make('Импорт (только чтение)')
                    ->description('Заполняется при переносе из кандидатов внешнего импорта.')
                    ->collapsed()
                    ->visible(fn (?Review $record): bool => $record !== null
                        && (filled($record->source_provider) || $record->review_import_source_id !== null || $record->imported_at !== null))
                    ->schema([
                        TextInput::make('source_provider')
                            ->label('Провайдер')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('source_url')
                            ->label('Ссылка на оригинал')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        DateTimePicker::make('imported_at')
                            ->label('Импортировано')
                            ->disabled()
                            ->dehydrated(false)
                            ->seconds(false),
                    ])
                    ->columns(2),

                Section::make('Модерация')
                    ->description('Для отзывов с сайта: дата отправки и заметки. Статус публикации — в колонке справа или кнопками в списке.')
                    ->schema([
                        DateTimePicker::make('submitted_at')
                            ->label('Отправлено (с сайта)')
                            ->disabled()
                            ->dehydrated(false)
                            ->seconds(false)
                            ->visible(fn (?Review $record): bool => $record !== null && $record->submitted_at !== null),
                        DateTimePicker::make('moderated_at')
                            ->label('Решение модерации')
                            ->disabled()
                            ->dehydrated(false)
                            ->seconds(false)
                            ->visible(fn (?Review $record): bool => $record !== null && $record->moderated_at !== null),
                        Textarea::make('moderation_note')
                            ->label('Комментарий модератора')
                            ->rows(2)
                            ->maxLength(2000),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return AdminEmptyState::applyInitial(
            $table
                ->columns([
                    TextColumn::make('id')->sortable(),
                    TextColumn::make('name')->searchable()->sortable(),
                    TextColumn::make('city')->placeholder('—'),
                    TextColumn::make('display_body')
                        ->label('Текст')
                        ->limit(40)
                        ->placeholder('—'),
                    TextColumn::make('rating'),
                    TextColumn::make('motorcycle.name')
                        ->label('Каталог аренды')
                        ->placeholder('—')
                        ->visible(fn (): bool => in_array(
                            (string) (currentTenant()?->themeKey() ?? ''),
                            ['moto', 'default'],
                            true,
                        )),
                    TextColumn::make('status')->badge()->formatStateUsing(fn (?string $state): string => $state ? (Review::statuses()[$state] ?? $state) : ''),
                    TextColumn::make('submitted_at')->label('Отправлено')->dateTime('d.m.Y H:i')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                    IconColumn::make('is_featured')->boolean(),
                    TextColumn::make('created_at')->date('d.m.Y')->sortable(),
                ])
                ->filters([
                    SelectFilter::make('status')->options(Review::statuses()),
                ])
                ->defaultSort('sort_order')
                ->recordActions([
                    Action::make('approve')
                        ->label('Опубликовать')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (Review $record): bool => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Опубликовать отзыв на сайте?')
                        ->action(function (Review $record): void {
                            $record->update([
                                'status' => 'published',
                                'moderated_at' => now(),
                                'moderated_by' => Auth::id(),
                            ]);
                            Notification::make()->title('Отзыв опубликован')->success()->send();
                        }),
                    Action::make('reject')
                        ->label('Отклонить')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Review $record): bool => $record->status === 'pending')
                        ->form([
                            Textarea::make('moderation_note')->label('Комментарий (необязательно)')->rows(2),
                        ])
                        ->action(function (Review $record, array $data): void {
                            $note = isset($data['moderation_note']) ? trim((string) $data['moderation_note']) : '';
                            $record->update([
                                'status' => 'hidden',
                                'moderated_at' => now(),
                                'moderated_by' => Auth::id(),
                                'moderation_note' => $note !== '' ? $note : null,
                            ]);
                            Notification::make()->title('Отзыв отклонён')->success()->send();
                        }),
                    EditAction::make(),
                ]),
            'Пока нет отзывов',
            'Добавьте отзыв вручную или дождитесь отправки с сайта — новые сначала в статусе «На модерации». '
                .'Чтобы отзывы появились в блоке на сайте, опубликуйте их.'
                .AdminEmptyState::hintFiltersAndSearch(),
            'heroicon-o-star',
            [CreateAction::make()->label('Добавить отзыв')],
        );
    }

    /**
     * Slug темы: записи {@see TenantServiceProgram} (в UI каталога — «Программы» или «Услуги» по теме),
     * для black_duck ещё страницы, плюс ключи из отзывов тенанта.
     *
     * @return array<string, string> value => label
     */
    private static function reviewCategoryKeySelectOptions(): array
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            return [];
        }

        $tenantId = (int) $tenant->id;
        $theme = $tenant->themeKey();
        $options = [];

        $programs = TenantServiceProgram::query()
            ->where('tenant_id', $tenantId)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['slug', 'title']);

        foreach ($programs as $program) {
            $slug = trim((string) $program->slug);
            if (! self::canUseReviewCategoryKey($slug)) {
                continue;
            }
            $options[$slug] = "{$program->title} · {$slug}";
        }

        if ($theme === 'black_duck') {
            $pages = Page::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'published')
                ->orderBy('name')
                ->get(['slug', 'name']);

            foreach ($pages as $page) {
                $slug = trim((string) $page->slug);
                if (! self::canUseReviewCategoryKey($slug) || array_key_exists($slug, $options)) {
                    continue;
                }
                $options[$slug] = "{$page->name} · {$slug} (страница)";
            }
        }

        $fromReviews = Review::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('category_key')
            ->where('category_key', '!=', '')
            ->distinct()
            ->orderBy('category_key')
            ->pluck('category_key');

        foreach ($fromReviews as $key) {
            $k = trim((string) $key);
            if (! self::canUseReviewCategoryKey($k) || array_key_exists($k, $options)) {
                continue;
            }
            $options[$k] = "{$k} (из отзывов)";
        }

        return $options;
    }

    private static function canUseReviewCategoryKey(string $key): bool
    {
        return $key !== '' && mb_strlen($key) <= self::REVIEW_CATEGORY_KEY_MAX_LENGTH;
    }

    /**
     * Тексты подсказки: для black_duck в меню «Услуги», для expert_auto — «Программы» (как в {@see TenantServiceProgramResource}).
     *
     * @return list<string>
     */
    private static function reviewCategoryKeyTooltipLines(): array
    {
        $theme = (string) (currentTenant()?->themeKey() ?? '');
        $lines = [
            'Связь с темой или карточкой каталога для фильтра и бейджа в блоке отзывов.',
        ];

        if (in_array($theme, ['expert_auto', 'expert_pr', 'black_duck'], true)) {
            $catalogNav = self::tenantCatalogUsesServicesLabel() ? 'Услуги' : 'Программы';
            $lines[] = "В списке — видимые позиции из «Каталог → {$catalogNav}» (поле slug), затем ключи из других отзывов этого клиента.";
        } else {
            $lines[] = 'В списке — ключи из отзывов этого клиента; если в базе есть карточки каталога (программы/услуги), их slug тоже появятся здесь.';
        }

        if ($theme === 'black_duck') {
            $lines[] = 'Дополнительно — slug опубликованных страниц, если он ещё не занят карточкой услуги.';
        }

        $lines[] = 'Пусто — отзыв без темы. Новый slug обычно задаётся при создании карточки в каталоге.';

        return $lines;
    }

    /** Согласовано с подписью раздела в {@see TenantServiceProgramResource} (Услуги vs Программы). */
    private static function tenantCatalogUsesServicesLabel(): bool
    {
        $t = currentTenant();

        return $t !== null && $t->themeKey() === 'black_duck';
    }

    /**
     * Вложенные маршруты импорта зарегистрированы на этом ресурсе; сами страницы задают {@see ReviewImportSource} /
     * {@see ReviewImportCandidate} и делегируют формы/таблицы в {@see ReviewImportSourceResource}.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'import_sources' => Pages\ListReviewImportSources::route('/sources'),
            'import_sources_create' => Pages\CreateReviewImportSource::route('/sources/create'),
            'import_sources_edit' => Pages\EditReviewImportSource::route('/sources/{record}/edit'),
            'import_candidates' => Pages\ListReviewImportCandidates::route('/candidates'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
