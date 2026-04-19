<?php

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Pages\TenantLogin;
use App\Filament\Tenant\Resources\CalendarOccupancyMappingResource;
use App\Filament\Tenant\Resources\SchedulingTargetResource;
use App\Models\CalendarConnection;
use App\Models\CalendarSubscription;
use App\Models\SchedulingResource;
use App\Models\Tenant;
use App\Models\User;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use App\Scheduling\SchedulingTimezoneOptions;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Pages\Page as FilamentPage;
use Filament\Resources\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * GET each tenant Filament URL (dashboard, custom pages, resource index + create where registered).
 * Asserts HTTP 200, no redirect/Location, closing `</html>`, panel markers (`wire:snapshot`|`data-livewire-id` + `fi-` prefix),
 * forbidden substrings (HTML with `<script>`/`<style>` stripped so Livewire bundles do not false-positive `livewire-error`),
 * optional per-request query budget, no ERROR/CRITICAL/EMERGENCY log line headers, then clears laravel.log.
 *
 * Failures accumulate for all URLs; one final assert. `$strictHtmlAssertions` disables the forbidden list only.
 *
 * Note: Edit/view routes that require a record are not enumerated here; extend with factories if needed.
 *
 * Gated create pages (scheduling prerequisites): см. {@see test_gated_scheduling_create_urls_redirect_without_prerequisites()}
 * и {@see test_gated_scheduling_create_urls_return_200_with_prerequisites()}.
 */
class TenantAdminPanelSmokeTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    /** When false, skips default forbidden substring checks; core checks (status, redirect, </html>, markers) remain. */
    protected bool $strictHtmlAssertions = true;

    /**
     * When true, enforces maxQueriesPerRequest per URL (off by default — avoids env flakiness).
     * With parallel PHPUnit processes, DB query log can be less predictable; keep off for typical feature smoke.
     */
    protected bool $assertQueryBudget = false;

    protected int $maxQueriesPerRequest = 100;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->logPath = storage_path('logs/laravel.log');
    }

    protected function tearDown(): void
    {
        if ($this->assertQueryBudget) {
            DB::disableQueryLog();
        }
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    protected function getWithHost(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    public function test_tenant_admin_get_routes_load_without_errors_and_log_stays_clean(): void
    {
        File::ensureDirectoryExists(dirname($this->logPath));
        file_put_contents($this->logPath, '');

        // expert_auto-only resources (e.g. TenantServiceProgramResource::canAccess) require this theme key.
        $tenant = $this->createTenantWithActiveDomain('smoke', ['theme_key' => 'expert_auto']);
        $host = $this->tenancyHostForSlug('smoke');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $urls = $this->collectTenantAdminGetUrls($tenant);
        $seen = [];

        if ($this->assertQueryBudget) {
            DB::enableQueryLog();
            DB::flushQueryLog();
        }

        $pageFailures = [];
        foreach ($urls as ['label' => $label, 'path' => $path]) {
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;

            if ($this->assertQueryBudget) {
                DB::flushQueryLog();
            }

            $response = $this->getWithHost($host, $path);

            if ($this->assertQueryBudget) {
                $queryCount = count(DB::getQueryLog());
                if ($queryCount > $this->maxQueriesPerRequest) {
                    $pageFailures[] = sprintf(
                        '%s %s → query budget exceeded (%d > %d)',
                        $label,
                        $path,
                        $queryCount,
                        $this->maxQueriesPerRequest
                    );
                }
                DB::flushQueryLog();
            }

            $pageFailures = array_merge(
                $pageFailures,
                $this->collectTenantAdminPageFailures($response, $label, $path)
            );
        }

        $logContent = is_file($this->logPath) ? (string) file_get_contents($this->logPath) : '';

        file_put_contents($this->logPath, '');

        $logFailures = $this->findLogSeverityIssues($logContent);

        $messages = [];
        if ($pageFailures !== []) {
            $messages[] = "Page:\n".implode("\n", $pageFailures);
            if ($logContent !== '') {
                $messages[] = '--- log head ---'."\n".$this->headString($logContent, 4000);
            }
        }
        if ($logFailures !== []) {
            $messages[] = "Log:\n".implode("\n", $logFailures);
            if ($logContent !== '') {
                $messages[] = '--- log tail ---'."\n".$this->tailString($logContent, 6000);
            }
        }

        $this->assertSame([], [...$pageFailures, ...$logFailures], implode("\n\n", $messages));
    }

    public function test_gated_scheduling_create_urls_redirect_without_prerequisites(): void
    {
        File::ensureDirectoryExists(dirname($this->logPath));
        file_put_contents($this->logPath, '');

        $tenant = $this->createTenantWithActiveDomain('smoke-gated-off', ['theme_key' => 'expert_auto']);
        $host = $this->tenancyHostForSlug('smoke-gated-off');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $pageFailures = [];
        foreach ($this->gatedSchedulingCreateLabels() as $label) {
            $resourceClass = str_replace('::create', '', $label);
            app(CurrentTenantManager::class)->setTenant($tenant);
            $path = parse_url($resourceClass::getUrl('create', [], false, 'admin'), PHP_URL_PATH) ?: '';
            if ($path === '') {
                $pageFailures[] = sprintf('%s → could not resolve create path', $label);

                continue;
            }
            $response = $this->getWithHost($host, $path);
            $indexPath = $this->gatedCreateIndexPathForLabel($tenant, $label);
            $pageFailures = array_merge(
                $pageFailures,
                $this->collectTenantAdminPageFailures($response, $label, $path, true, $indexPath)
            );
        }

        $logContent = is_file($this->logPath) ? (string) file_get_contents($this->logPath) : '';
        file_put_contents($this->logPath, '');
        $logFailures = $this->findLogSeverityIssues($logContent);

        $messages = [];
        if ($pageFailures !== []) {
            $messages[] = "Page:\n".implode("\n", $pageFailures);
        }
        if ($logFailures !== []) {
            $messages[] = "Log:\n".implode("\n", $logFailures);
        }

        $this->assertSame([], [...$pageFailures, ...$logFailures], implode("\n\n", $messages));
    }

    public function test_gated_scheduling_create_urls_return_200_with_prerequisites(): void
    {
        File::ensureDirectoryExists(dirname($this->logPath));
        file_put_contents($this->logPath, '');

        $tenant = $this->createTenantWithActiveDomain('smoke-gated-on', ['theme_key' => 'expert_auto']);
        $host = $this->tenancyHostForSlug('smoke-gated-on');

        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->seedSchedulingSmokePrerequisites($tenant);

        $pageFailures = [];
        foreach ($this->gatedSchedulingCreateLabels() as $label) {
            $resourceClass = str_replace('::create', '', $label);
            app(CurrentTenantManager::class)->setTenant($tenant);
            $path = parse_url($resourceClass::getUrl('create', [], false, 'admin'), PHP_URL_PATH) ?: '';
            if ($path === '') {
                $pageFailures[] = sprintf('%s → could not resolve create path', $label);

                continue;
            }
            $response = $this->getWithHost($host, $path);
            $pageFailures = array_merge(
                $pageFailures,
                $this->collectTenantAdminPageFailures($response, $label, $path)
            );
        }

        $logContent = is_file($this->logPath) ? (string) file_get_contents($this->logPath) : '';
        file_put_contents($this->logPath, '');
        $logFailures = $this->findLogSeverityIssues($logContent);

        $messages = [];
        if ($pageFailures !== []) {
            $messages[] = "Page:\n".implode("\n", $pageFailures);
        }
        if ($logFailures !== []) {
            $messages[] = "Log:\n".implode("\n", $logFailures);
        }

        $this->assertSame([], [...$pageFailures, ...$logFailures], implode("\n\n", $messages));
    }

    /**
     * @param  non-empty-string|null  $gatedRedirectIndexPath  path-only, e.g. /admin/scheduling-targets
     */
    private function collectTenantAdminPageFailures(
        TestResponse $response,
        string $label,
        string $path,
        bool $allowGatedCreateRedirect = false,
        ?string $gatedRedirectIndexPath = null,
    ): array {
        $html = $response->getContent();
        $preview = $this->normalizedResponsePreview($html);
        $failures = [];

        if ($allowGatedCreateRedirect && $gatedRedirectIndexPath !== null && $gatedRedirectIndexPath !== '') {
            if ($response->status() === 200) {
                $failures[] = sprintf(
                    '%s %s → expected redirect to index without prerequisites, got HTTP 200 | preview: %s',
                    $label,
                    $path,
                    $preview
                );

                return $failures;
            }

            if ($response->isRedirect()) {
                $location = (string) $response->headers->get('Location', '');
                $locPath = parse_url($location, PHP_URL_PATH) ?: '';

                if ($locPath === $gatedRedirectIndexPath || str_ends_with($location, $gatedRedirectIndexPath)) {
                    return [];
                }

                $failures[] = sprintf(
                    '%s %s → expected redirect to %s, got Location %s | preview: %s',
                    $label,
                    $path,
                    $gatedRedirectIndexPath,
                    $location,
                    $preview
                );

                return $failures;
            }

            $failures[] = sprintf(
                '%s %s → expected redirect without prerequisites, got HTTP %s | preview: %s',
                $label,
                $path,
                $response->status(),
                $preview
            );

            return $failures;
        }

        if ($response->status() !== 200) {
            $failures[] = sprintf('%s %s → HTTP %s | preview: %s', $label, $path, $response->status(), $preview);

            return $failures;
        }

        // Redundant for normal 3xx after the guard above, but catches odd/custom response implementations.
        if ($response->isRedirect()) {
            $failures[] = sprintf('%s %s → unexpected redirect (status %s) | preview: %s', $label, $path, $response->status(), $preview);

            return $failures;
        }

        if ($response->headers->has('Location')) {
            $failures[] = sprintf('%s %s → unexpected Location header | preview: %s', $label, $path, $preview);
        }

        if (! str_contains($html, '</html>')) {
            $failures[] = sprintf('%s %s → missing closing </html> | preview: %s', $label, $path, $preview);
        }

        $hasLivewireMarker = str_contains($html, 'wire:snapshot') || str_contains($html, 'data-livewire-id');
        // Broad but stable Filament class prefix; if unrelated copy ever contains "fi-", tighten (e.g. fi-body, fi-main).
        $hasFiPrefix = str_contains($html, 'fi-');
        if (! $hasLivewireMarker || ! $hasFiPrefix) {
            $failures[] = sprintf(
                '%s %s → panel marker missing (wire:snapshot|data-livewire-id: %s, fi-: %s) | preview: %s',
                $label,
                $path,
                $hasLivewireMarker ? 'yes' : 'no',
                $hasFiPrefix ? 'yes' : 'no',
                $preview
            );
        }

        if ($this->strictHtmlAssertions) {
            $htmlForScan = $this->stripScriptAndStyleBlocks($html);
            foreach ($this->defaultForbiddenHtmlSubstrings() as $needle) {
                if ($needle === 'livewire-error') {
                    if (str_contains(strtolower($htmlForScan), 'livewire-error')) {
                        $failures[] = sprintf('%s %s → forbidden substring in HTML: %s | preview: %s', $label, $path, $needle, $preview);
                    }

                    continue;
                }
                if (str_contains($htmlForScan, $needle)) {
                    $failures[] = sprintf('%s %s → forbidden substring in HTML: %s | preview: %s', $label, $path, $needle, $preview);
                }
            }
        }

        return $failures;
    }

    /**
     * Remove script/style so bundled JS (e.g. Livewire mentioning "livewire-error") does not false-positive forbidden scans.
     */
    private function stripScriptAndStyleBlocks(string $html): string
    {
        $withoutScripts = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? '';
        $withoutStyles = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $withoutScripts) ?? '';

        return $withoutStyles;
    }

    /**
     * Default forbidden substrings (plan §3). Do not add broad bans (error/exception/failed).
     *
     * @return list<string>
     */
    private function defaultForbiddenHtmlSubstrings(): array
    {
        return [
            'Livewire\\Exceptions\\',
            'Livewire\\Exceptions',
            'Whoops\\',
            'Illuminate\\View\\ViewException',
            'Symfony\\Component\\ErrorHandler\\',
            'livewire-error',
            'Stack trace:',
        ];
    }

    private function normalizedResponsePreview(string $html): string
    {
        $collapsed = preg_replace('/\s+/', ' ', $html);
        $trimmed = trim((string) $collapsed);

        if (function_exists('mb_substr')) {
            return mb_substr($trimmed, 0, 2000, 'UTF-8');
        }

        return substr($trimmed, 0, 2000);
    }

    /**
     * @return list<array{label: string, path: string}>
     */
    private function collectTenantAdminGetUrls(Tenant $tenant): array
    {
        $panel = Filament::getPanel('admin');
        $items = [];

        $tenantManager = app(CurrentTenantManager::class);
        $previousTenant = $tenantManager->getTenant();
        $tenantManager->setTenant($tenant);

        try {
            foreach ($panel->getPages() as $pageClass) {
                if (! is_subclass_of($pageClass, FilamentPage::class)) {
                    continue;
                }
                if ($pageClass === TenantLogin::class || is_a($pageClass, Login::class, true)) {
                    continue;
                }

                $path = parse_url($pageClass::getUrl([], false, 'admin'), PHP_URL_PATH);
                if (! is_string($path) || $path === '') {
                    continue;
                }
                $items[] = ['label' => $pageClass, 'path' => $path];
            }

            foreach ($panel->getResources() as $resourceClass) {
                if (! is_subclass_of($resourceClass, Resource::class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($resourceClass);
                if ($reflection->isAbstract()) {
                    continue;
                }

                if (method_exists($resourceClass, 'canAccess') && ! $resourceClass::canAccess()) {
                    continue;
                }

                $items[] = [
                    'label' => $resourceClass.'::index',
                    'path' => parse_url($resourceClass::getUrl(null, [], false, 'admin'), PHP_URL_PATH) ?: '/admin',
                ];

                if ($this->tenantAdminSmokeShouldIncludeResourceCreate($resourceClass)) {
                    $items[] = [
                        'label' => $resourceClass.'::create',
                        'path' => parse_url($resourceClass::getUrl('create', [], false, 'admin'), PHP_URL_PATH) ?: '/admin',
                    ];
                }
            }
        } finally {
            $tenantManager->setTenant($previousTenant);
        }

        usort($items, static fn (array $a, array $b): int => $a['path'] <=> $b['path']);

        return $items;
    }

    /**
     * Create в smoke только если страница есть, Filament canCreate() (если false — пропуск) и выполнены
     * контекстные предусловия для scheduling-ресурсов с осознанным redirect-контрактом.
     */
    private function tenantAdminSmokeShouldIncludeResourceCreate(string $resourceClass): bool
    {
        if (! $resourceClass::hasPage('create')) {
            return false;
        }

        if (method_exists($resourceClass, 'canCreate') && $resourceClass::canCreate() === false) {
            return false;
        }

        if ($resourceClass === SchedulingTargetResource::class) {
            return SchedulingTargetResource::canStartCreatingTarget();
        }

        if ($resourceClass === CalendarOccupancyMappingResource::class) {
            return CalendarOccupancyMappingResource::canStartCreatingMapping();
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function gatedSchedulingCreateLabels(): array
    {
        return [
            CalendarOccupancyMappingResource::class.'::create',
            SchedulingTargetResource::class.'::create',
        ];
    }

    private function gatedCreateIndexPathForLabel(Tenant $tenant, string $label): ?string
    {
        $map = [
            CalendarOccupancyMappingResource::class.'::create' => CalendarOccupancyMappingResource::class,
            SchedulingTargetResource::class.'::create' => SchedulingTargetResource::class,
        ];
        if (! isset($map[$label])) {
            return null;
        }
        $resourceClass = $map[$label];
        $tenantManager = app(CurrentTenantManager::class);
        $previousTenant = $tenantManager->getTenant();
        $tenantManager->setTenant($tenant);
        try {
            $p = parse_url($resourceClass::getUrl('index', [], false, 'admin'), PHP_URL_PATH);

            return is_string($p) && $p !== '' ? $p : null;
        } finally {
            $tenantManager->setTenant($previousTenant);
        }
    }

    private function seedSchedulingSmokePrerequisites(Tenant $tenant): void
    {
        $tenant->update([
            'scheduling_module_enabled' => true,
            'calendar_integrations_enabled' => true,
        ]);

        SchedulingResource::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'resource_type' => SchedulingResourceType::Person->value,
            'user_id' => null,
            'label' => 'Smoke scheduling resource',
            'timezone' => SchedulingTimezoneOptions::DEFAULT_IDENTIFIER,
            'tentative_events_policy' => TentativeEventsPolicy::ProviderDefault,
            'unconfirmed_requests_policy' => UnconfirmedRequestsPolicy::Ignore,
            'is_active' => true,
        ]);

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'Smoke calendar',
            'is_active' => true,
        ]);
        CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'title' => 'Smoke subscription',
            'use_for_busy' => true,
            'use_for_write' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Only real Laravel/Monolog log entry headers at line start (not stack trace or JSON fragments).
     *
     * @return list<string>
     */
    private function findLogSeverityIssues(string $content): array
    {
        if ($content === '') {
            return [];
        }

        // Channel before level: allow hyphenated names (e.g. custom Monolog channels), not only [\w.]+
        $pattern = '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?\]\s+[-\w.]+\.(ERROR|CRITICAL|EMERGENCY):/m';

        $issues = [];
        if (preg_match_all($pattern, $content, $m)) {
            foreach ($m[0] as $match) {
                $issues[] = trim($match);
            }
        }

        return array_values(array_unique($issues));
    }

    private function tailString(string $value, int $maxBytes): string
    {
        if (strlen($value) <= $maxBytes) {
            return $value;
        }

        return substr($value, -$maxBytes);
    }

    private function headString(string $value, int $maxBytes): string
    {
        if (strlen($value) <= $maxBytes) {
            return $value;
        }

        return substr($value, 0, $maxBytes);
    }
}
