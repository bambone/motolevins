<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Platform\Pages\Concerns\GrantsPlatformPageAccess;
use App\Models\PlatformSetting;
use App\Services\Seo\InitializePlatformMarketingSeoDefaults;
use App\Services\Seo\PlatformMarketingLlmsBodyRenderer;
use App\Services\Seo\PlatformMarketingLlmsGenerator;
use App\Services\Seo\PlatformMarketingPublicBaseUrl;
use App\Services\Seo\PlatformMarketingRobotsBody;
use App\Services\Seo\PlatformMarketingSitemapXml;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JsonException;
use UnitEnum;

class PlatformMarketingSeoPage extends Page
{
    use GrantsPlatformPageAccess;

    protected static ?string $navigationLabel = 'SEO маркетинга';

    protected static ?string $title = 'SEO: маркетинговый сайт (robots, sitemap, llms)';

    protected static ?string $slug = 'marketing-seo';

    protected static string|UnitEnum|null $navigationGroup = 'Платформа';

    protected static ?int $navigationSort = 13;

    protected static ?string $panel = 'platform';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected string $view = 'filament.pages.platform.marketing-seo';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->getSchema('form')->fill($this->loadFormState());
    }

    public function form(Schema $schema): Schema
    {
        $base = app(PlatformMarketingPublicBaseUrl::class)->resolve();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Публичные URL')
                    ->description('Контур central domains (маркетинг). Файлы отдаются «живыми» контроллерами, без снимков как у тенантов. Счётчики Яндекс Метрики и GA4 — на странице «Маркетинг и контент».')
                    ->schema([
                        Placeholder::make('url_robots')
                            ->label('robots.txt')
                            ->content($base.'/robots.txt'),
                        Placeholder::make('url_sitemap')
                            ->label('sitemap.xml')
                            ->content($base.'/sitemap.xml'),
                        Placeholder::make('url_llms')
                            ->label('llms.txt')
                            ->content($base.'/llms.txt'),
                    ])->columns(1),

                Section::make('robots.txt')
                    ->description('По умолчанию — шаблон с Allow/Disallow и Sitemap. Включите свой текст для полного переопределения.')
                    ->schema([
                        Toggle::make('seo_custom_robots_enabled')
                            ->label('Полный свой robots.txt')
                            ->helperText('marketing.seo.custom_robots_enabled'),
                        Textarea::make('seo_robots_txt')
                            ->label('Текст robots.txt')
                            ->rows(12)
                            ->columnSpanFull()
                            ->helperText('marketing.seo.robots_txt — только если включён режим выше и поле не пустое.'),
                    ])->columns(1),

                Section::make('sitemap.xml')
                    ->description('JSON-массив путей (строки). Пусто — список из config/platform_marketing.php → marketing_public_paths.')
                    ->schema([
                        Textarea::make('seo_sitemap_paths_json')
                            ->label('Пути (JSON-массив)')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('marketing.seo.sitemap_paths, например ["/","/features"]'),
                    ]),

                Section::make('llms.txt')
                    ->description('Как у тенантов: введение и список {path, summary}. Пустые поля — fallback из конфига и генератора.')
                    ->schema([
                        Textarea::make('seo_llms_intro')
                            ->label('Введение')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('marketing.seo.llms_intro'),
                        Textarea::make('seo_llms_entries_json')
                            ->label('Список URL (JSON)')
                            ->rows(10)
                            ->columnSpanFull()
                            ->helperText('marketing.seo.llms_entries — [{"path":"/","summary":"…"},…]'),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->getSchema('form')->getState();

        if (! $this->validateSeoJson($state)) {
            return;
        }

        PlatformSetting::set(
            'marketing.seo.custom_robots_enabled',
            ! empty($state['seo_custom_robots_enabled']),
            'boolean',
        );
        PlatformSetting::set(
            'marketing.seo.robots_txt',
            trim((string) ($state['seo_robots_txt'] ?? '')),
            'string',
        );

        $rawSitemap = trim((string) ($state['seo_sitemap_paths_json'] ?? ''));
        if ($rawSitemap === '') {
            PlatformSetting::query()->where('key', 'marketing.seo.sitemap_paths')->delete();
            Cache::forget('platform_settings.marketing.seo.sitemap_paths');
        } else {
            try {
                $decoded = json_decode($rawSitemap, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Notification::make()->title('Sitemap paths: невалидный JSON')->body($e->getMessage())->danger()->send();

                return;
            }
            if (! is_array($decoded) || array_is_list($decoded) === false) {
                Notification::make()->title('Sitemap paths: ожидается JSON-массив [...]')->danger()->send();

                return;
            }
            $paths = [];
            foreach ($decoded as $item) {
                if (is_string($item) && $item !== '') {
                    $paths[] = $item;
                }
            }
            PlatformSetting::set('marketing.seo.sitemap_paths', $paths, 'json');
        }

        PlatformSetting::set(
            'marketing.seo.llms_intro',
            trim((string) ($state['seo_llms_intro'] ?? '')),
            'string',
        );
        PlatformSetting::set(
            'marketing.seo.llms_entries',
            trim((string) ($state['seo_llms_entries_json'] ?? '')),
            'string',
        );

        Notification::make()->title('Сохранено')->success()->send();
        $this->getSchema('form')->fill($this->loadFormState());
    }

    protected function getHeaderActions(): array
    {
        $base = app(PlatformMarketingPublicBaseUrl::class)->resolve();

        return [
            ActionGroup::make([
                Action::make('previewRobots')
                    ->label('Предпросмотр robots')
                    ->icon('heroicon-m-eye')
                    ->modalHeading('Предпросмотр robots.txt')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn () => view('filament.partials.seo-text-preview', [
                        'content' => $this->previewRobotsBody(),
                    ]))
                    ->modalSubmitAction(false),

                Action::make('openRobots')
                    ->label('Открыть robots.txt')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url($base.'/robots.txt')
                    ->openUrlInNewTab(),

                Action::make('previewSitemap')
                    ->label('Предпросмотр sitemap')
                    ->icon('heroicon-m-eye')
                    ->modalHeading('Предпросмотр sitemap.xml')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn () => view('filament.partials.seo-text-preview', [
                        'content' => $this->previewSitemapBody(),
                    ]))
                    ->modalSubmitAction(false),

                Action::make('openSitemap')
                    ->label('Открыть sitemap.xml')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url($base.'/sitemap.xml')
                    ->openUrlInNewTab(),

                Action::make('previewLlms')
                    ->label('Предпросмотр llms')
                    ->icon('heroicon-m-eye')
                    ->modalHeading('Предпросмотр llms.txt')
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalContent(fn () => view('filament.partials.seo-text-preview', [
                        'content' => $this->previewLlmsBody(),
                    ]))
                    ->modalSubmitAction(false),

                Action::make('openLlms')
                    ->label('Открыть llms.txt')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url($base.'/llms.txt')
                    ->openUrlInNewTab(),
            ])
                ->label('Публичные файлы')
                ->icon('heroicon-s-document-text')
                ->color('gray')
                ->button()
                ->dropdownWidth('fi-platform-marketing-seo-dropdown'),

            ActionGroup::make([
                Action::make('seoDefaults')
                    ->label('Заполнить llms по умолчанию')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalHeading('Заполнить llms из конфигурации?')
                    ->modalDescription('Записывает marketing.seo.llms_intro и marketing.seo.llms_entries, только если они сейчас пустые.')
                    ->action(function (): void {
                        $messages = app(InitializePlatformMarketingSeoDefaults::class)->execute(false);
                        $this->getSchema('form')->fill($this->loadFormState());
                        Notification::make()
                            ->title('SEO')
                            ->body($messages !== [] ? implode("\n", $messages) : 'Поля уже были заполнены.')
                            ->success()
                            ->send();
                    }),

                Action::make('seoDefaultsForce')
                    ->label('Перезаписать llms')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Перезаписать llms?')
                    ->modalDescription('Принудительно обновит введение и список ссылок из генератора по конфигу.')
                    ->action(function (): void {
                        $messages = app(InitializePlatformMarketingSeoDefaults::class)->execute(true);
                        $this->getSchema('form')->fill($this->loadFormState());
                        Notification::make()
                            ->title('SEO')
                            ->body(implode("\n", $messages))
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Автозаполнение')
                ->icon('heroicon-s-sparkles')
                ->color('primary')
                ->button()
                ->dropdownWidth('fi-platform-marketing-seo-dropdown'),
        ];
    }

    private function loadFormState(): array
    {
        $paths = PlatformSetting::get('marketing.seo.sitemap_paths', null);
        $pathsJson = is_array($paths) && $paths !== []
            ? json_encode($paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : '';

        return [
            'seo_custom_robots_enabled' => (bool) PlatformSetting::get('marketing.seo.custom_robots_enabled', false),
            'seo_robots_txt' => (string) PlatformSetting::get('marketing.seo.robots_txt', ''),
            'seo_sitemap_paths_json' => $pathsJson,
            'seo_llms_intro' => (string) PlatformSetting::get('marketing.seo.llms_intro', ''),
            'seo_llms_entries_json' => (string) PlatformSetting::get('marketing.seo.llms_entries', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function validateSeoJson(array $state): bool
    {
        $entries = trim((string) ($state['seo_llms_entries_json'] ?? ''));
        if ($entries !== '') {
            json_decode($entries, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Notification::make()->title('llms entries: невалидный JSON')->body(json_last_error_msg())->danger()->send();

                return false;
            }
            $decoded = json_decode($entries, true);
            if (! is_array($decoded) || array_is_list($decoded) === false) {
                Notification::make()->title('llms entries: ожидается JSON-массив [...]')->danger()->send();

                return false;
            }
            foreach ($decoded as $i => $row) {
                if (! is_array($row) || trim((string) ($row['path'] ?? '')) === '') {
                    Notification::make()->title('llms entries: элемент #'.((int) $i + 1).' — нужен объект с path')->danger()->send();

                    return false;
                }
            }
        }

        $sitemap = trim((string) ($state['seo_sitemap_paths_json'] ?? ''));
        if ($sitemap !== '') {
            json_decode($sitemap, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Notification::make()->title('Sitemap paths: невалидный JSON')->body(json_last_error_msg())->danger()->send();

                return false;
            }
        }

        return true;
    }

    private function previewRobotsBody(): string
    {
        if ((bool) PlatformSetting::get('marketing.seo.custom_robots_enabled', false)) {
            $custom = trim((string) PlatformSetting::get('marketing.seo.robots_txt', ''));
            if ($custom !== '') {
                return $custom;
            }
        }
        $base = app(PlatformMarketingPublicBaseUrl::class)->resolve();

        return app(PlatformMarketingRobotsBody::class)->build($base.'/sitemap.xml');
    }

    private function previewSitemapBody(): string
    {
        $base = app(PlatformMarketingPublicBaseUrl::class)->resolve();
        $paths = PlatformSetting::get('marketing.seo.sitemap_paths', null);
        if (! is_array($paths) || $paths === []) {
            $paths = app(PlatformMarketingLlmsGenerator::class)->defaultPaths();
        } else {
            $paths = array_values(array_filter(array_map('strval', $paths), fn (string $p): bool => $p !== ''));
        }

        return app(PlatformMarketingSitemapXml::class)->build($base, $paths);
    }

    private function previewLlmsBody(): string
    {
        $baseUrl = app(PlatformMarketingPublicBaseUrl::class)->resolve();
        $parts = parse_url($baseUrl) ?: [];
        $host = (string) ($parts['host'] ?? 'localhost');
        $https = ($parts['scheme'] ?? 'https') === 'https';

        $request = Request::create($baseUrl.'/', 'GET', [], [], [], [
            'HTTP_HOST' => $host,
            'HTTPS' => $https ? 'on' : 'off',
            'SERVER_PORT' => $https ? '443' : '80',
        ]);

        return app(PlatformMarketingLlmsBodyRenderer::class)->render($request);
    }
}
