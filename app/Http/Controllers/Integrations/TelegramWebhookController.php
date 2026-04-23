<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotContentResolver;
use App\Services\Telegram\TelegramWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotContentResolver $contentResolver,
        TelegramWebhookHandler $handler,
    ): JsonResponse {
        if (! $this->passesSecretToken($request, $contentResolver)) {
            return response()->json(['ok' => false], 403);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            Log::notice('telegram_webhook: empty_body');

            return response()->json(['ok' => true]);
        }

        try {
            $handler->handle($payload);
        } catch (\Throwable $e) {
            Log::error('telegram_webhook: handler_exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function passesSecretToken(Request $request, TelegramBotContentResolver $contentResolver): bool
    {
        if (! $contentResolver->isWebhookSecretCheckEnabled()) {
            return true;
        }

        $expected = trim((string) config('services.telegram.webhook_secret', ''));
        if ($expected === '') {
            Log::warning('telegram_webhook: secret_check_enabled_but_missing_env');

            return false;
        }

        $header = $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if (! hash_equals($expected, (string) $header)) {
            Log::warning('telegram_webhook: invalid_secret_token');

            return false;
        }

        return true;
    }
}
