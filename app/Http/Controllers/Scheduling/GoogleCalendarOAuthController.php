<?php

declare(strict_types=1);

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\CalendarConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OAuth Google Calendar — заглушка маршрутов; обмен code→token и хранение в {@see CalendarConnection} подключаются отдельно.
 */
final class GoogleCalendarOAuthController extends Controller
{
    public function redirect(Request $request): JsonResponse
    {
        $clientId = config('scheduling.google.client_id');
        if ($clientId === null || $clientId === '') {
            return response()->json([
                'message' => 'Google OAuth is not configured (SCHEDULING_GOOGLE_CLIENT_ID).',
            ], 503);
        }

        // Placeholder: реальный redirect_uri и scope — в задаче интеграции Events/freeBusy.
        return response()->json([
            'message' => 'Google OAuth redirect is not implemented yet; configure redirect_uri and token storage.',
            'hint' => 'See config/scheduling.php and CalendarConnection credentials_encrypted.',
        ], 501);
    }

    public function callback(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Google OAuth callback handler not implemented.',
        ], 501);
    }
}
