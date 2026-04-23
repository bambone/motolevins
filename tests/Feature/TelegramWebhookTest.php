<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Services\Platform\PlatformNotificationSettings;
use App\Services\Telegram\TelegramBotContentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function webhookUrlForHost(string $hostWithScheme = 'http://apex.test'): string
    {
        $path = ltrim((string) config('telegram.webhook_path', 'webhooks/telegram'), '/');

        return rtrim($hostWithScheme, '/').'/'.$path;
    }

    public function test_start_in_private_sends_message_to_telegram(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $response = $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'));

        $response->assertOk()->assertJson(['ok' => true]);
        Http::assertSent(function ($request) {
            $body = $request->data();
            $text = is_array($body) ? (string) ($body['text'] ?? '') : '';

            return str_contains($request->url(), 'api.telegram.org')
                && str_contains($text, 'Добро пожаловать');
        });
    }

    public function test_help_and_status_send_replies(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/help'))->assertOk();
        Http::assertSent(function ($request) {
            $body = $request->data();
            $text = is_array($body) ? (string) ($body['text'] ?? '') : '';

            return str_contains($text, 'справка') || str_contains($text, '/start');
        });

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 3]], 200),
        ]);
        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/status'))->assertOk();
        Http::assertSent(fn ($request) => str_contains(
            is_array($request->data()) ? (string) ($request->data()['text'] ?? '') : '',
            'Бот активен'
        ));
    }

    public function test_start_with_bot_suffix_normalizes(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start@rentbase_bot'))->assertOk();
        Http::assertSent(fn ($request) => str_contains(
            is_array($request->data()) ? (string) ($request->data()['text'] ?? '') : '',
            'Добро пожаловать'
        ));
    }

    public function test_unknown_command_sends_fallback(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/unknowncmd'))->assertOk();
        Http::assertSent(fn ($request) => str_contains(
            is_array($request->data()) ? (string) ($request->data()['text'] ?? '') : '',
            'не распознана'
        ));
    }

    public function test_no_bot_token_skips_send_and_returns_200(): void
    {
        $settings = app(PlatformNotificationSettings::class);
        $settings->setChannelEnabled('telegram', true);
        $settings->setTelegramBotToken(null);
        Http::fake();

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'))->assertOk();

        Http::assertNothingSent();
    }

    public function test_invalid_secret_returns_403_without_telegram_call(): void
    {
        $this->seedTelegramBot();
        PlatformSetting::set(TelegramBotContentResolver::KEY_WEBHOOK_SECRET_ENABLED, true, 'boolean');
        config(['services.telegram.webhook_secret' => 'expected-secret']);
        Http::fake();

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ])->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_edited_message_returns_200_without_send(): void
    {
        $this->seedTelegramBot();
        Http::fake();

        $this->postJson($this->webhookUrlForHost(), [
            'update_id' => 1,
            'edited_message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => '/start',
                'chat' => ['id' => 111, 'type' => 'private'],
            ],
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_group_chat_does_not_send(): void
    {
        $this->seedTelegramBot();
        Http::fake();

        $this->postJson($this->webhookUrlForHost(), [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => '/start',
                'chat' => ['id' => -100, 'type' => 'group'],
            ],
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_telegram_api_error_still_returns_200(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'))->assertOk();
    }

    public function test_empty_db_uses_built_in_defaults_for_start(): void
    {
        $this->seedTelegramBot();
        // Keys cleared — no custom texts in platform_settings
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'))->assertOk();
        Http::assertSent(fn ($request) => str_contains(
            is_array($request->data()) ? (string) ($request->data()['text'] ?? '') : '',
            'Добро пожаловать'
        ));
    }

    public function test_webhook_post_is_not_a_redirect(): void
    {
        $this->seedTelegramBot();
        Http::fake();

        $response = $this->postJson($this->webhookUrlForHost(), $this->privateMessageUpdate('/start'));
        $this->assertFalse($response->isRedirect(), 'Telegram webhook must not return 30x (POST redirect breaks Bot API).');
        $response->assertOk();
    }

    public function test_webhook_rejected_on_tenant_site_host(): void
    {
        $this->createTenantWithActiveDomain('webhookblock');
        $this->seedTelegramBot();
        Http::fake();

        $host = $this->tenancyHostForSlug('webhookblock');
        $this->postJson($this->webhookUrlForHost('http://'.$host), $this->privateMessageUpdate('/start'))
            ->assertNotFound()
            ->assertJsonFragment(['ok' => false, 'error' => 'webhook_host_not_allowed']);

        Http::assertNothingSent();
    }

    public function test_webhook_accepts_platform_panel_host(): void
    {
        $this->seedTelegramBot();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost('http://platform.apex.test'), $this->privateMessageUpdate('/start'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_webhook_accepts_app_url_host_not_in_central_list(): void
    {
        $this->seedTelegramBot();
        config(['app.url' => 'http://api-only.example.test']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 2]], 200),
        ]);

        $this->postJson($this->webhookUrlForHost('http://api-only.example.test'), $this->privateMessageUpdate('/start'))
            ->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function privateMessageUpdate(string $text): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'chat' => ['id' => 111, 'type' => 'private'],
            ],
        ];
    }

    private function seedTelegramBot(): void
    {
        $settings = app(PlatformNotificationSettings::class);
        $settings->setChannelEnabled('telegram', true);
        $settings->setTelegramBotToken('test-telegram-token-'.bin2hex(random_bytes(4)));
    }
}
