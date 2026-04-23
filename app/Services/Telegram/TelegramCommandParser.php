<?php

namespace App\Services\Telegram;

/**
 * Parses the first line of a Telegram message into a command and optional payload (text after first space).
 *
 * Examples: /start, /start@mybot, /start payload here, /help@MyBot x.
 */
final class TelegramCommandParser
{
    /**
     * @return array{command: string, payload: ?string}|null null if the text is not a command (does not start with /)
     */
    public function parse(?string $text): ?array
    {
        if ($text === null) {
            return null;
        }
        $trim = trim($text);
        if ($trim === '' || ! str_starts_with($trim, '/')) {
            return null;
        }

        $line = explode("\n", $trim, 2)[0];
        $parts = preg_split('/\s+/', $line, 2);
        $cmdPart = $parts[0] ?? '';
        $payload = isset($parts[1]) ? trim((string) $parts[1]) : null;
        if ($payload === '') {
            $payload = null;
        }

        if (! preg_match('#^/([a-zA-Z0-9_]+)(?:@[a-zA-Z0-9_]+)?$#', $cmdPart, $m)) {
            return null;
        }

        return [
            'command' => strtolower($m[1]),
            'payload' => $payload,
        ];
    }
}
