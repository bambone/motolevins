<?php

namespace App\Product\CRM\Notifications;

use App\ContactChannels\VisitorContactNormalizer;
use App\Models\CrmRequest;

/**
 * Body for platform marketing contact Telegram alerts.
 * Uses HTML (escaped) + optional &lt;a&gt; for t.me when the visit left a Telegram handle.
 */
final class PlatformContactTelegramMessage
{
    /**
     * @return array{text: string, parse_mode: 'HTML'}
     */
    public static function build(CrmRequest $crm): array
    {
        $lines = [self::h('Новая заявка с маркетингового сайта'), ''];

        $lines[] = 'ID: '.self::h((string) $crm->id);
        $lines[] = 'Тип: '.self::h($crm->request_type);

        $name = trim((string) $crm->name);
        if ($name !== '') {
            $lines[] = 'Имя: '.self::h($name);
        }

        $phone = trim((string) $crm->phone);
        if ($phone !== '') {
            $lines[] = 'Телефон: '.self::h($phone);
        }

        $email = trim((string) ($crm->email ?? ''));
        if ($email !== '') {
            $lines[] = 'Email: '.self::h($email);
        }

        $pref = trim((string) ($crm->preferred_contact_channel ?? ''));
        if ($pref !== '') {
            $lines[] = 'Предпочтительный канал: '.self::h($pref);
        }

        $prefVal = trim((string) ($crm->preferred_contact_value ?? ''));
        if ($prefVal !== '') {
            $lines[] = self::contactChannelLine(
                $pref,
                $prefVal,
            );
        }

        $payload = is_array($crm->payload_json) ? $crm->payload_json : [];
        $intent = isset($payload['intent']) ? trim((string) $payload['intent']) : '';
        $intentLabel = isset($payload['intent_label']) ? trim((string) $payload['intent_label']) : '';
        if ($intent !== '' || $intentLabel !== '') {
            $lines[] = '';
            if ($intent !== '') {
                $lines[] = 'Intent: '.self::h($intent);
            }
            if ($intentLabel !== '' && $intentLabel !== $intent) {
                $lines[] = 'Intent (подпись): '.self::h($intentLabel);
            }
        }

        $message = trim((string) ($crm->message ?? ''));
        if ($message !== '') {
            $preview = mb_strlen($message) > 1200 ? mb_substr($message, 0, 1197).'…' : $message;
            $lines[] = '';
            $lines[] = 'Сообщение:';
            $lines[] = self::h($preview);
        }

        $utmLines = self::utmSectionLines($crm);
        if ($utmLines !== []) {
            $lines[] = '';
            $lines = array_merge($lines, $utmLines);
        }

        $text = implode("\n", $lines);
        $text = mb_substr($text, 0, 4096);

        return [
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
    }

    private static function contactChannelLine(string $channel, string $rawValue): string
    {
        if ($channel === 'telegram') {
            $handle = VisitorContactNormalizer::normalizeTelegram($rawValue);
            if ($handle !== null) {
                $url = 'https://t.me/'.rawurlencode($handle);
                $label = self::h($rawValue);

                return 'Контакт (канал): <a href="'.self::h($url).'">'.$label.'</a>';
            }
        }

        return 'Контакт (канал): '.self::h($rawValue);
    }

    private static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @return list<string>
     */
    private static function utmSectionLines(CrmRequest $crm): array
    {
        $pairs = [
            'UTM Source' => trim((string) ($crm->utm_source ?? '')),
            'UTM Medium' => trim((string) ($crm->utm_medium ?? '')),
            'UTM Campaign' => trim((string) ($crm->utm_campaign ?? '')),
            'UTM Content' => trim((string) ($crm->utm_content ?? '')),
            'UTM Term' => trim((string) ($crm->utm_term ?? '')),
        ];

        $nonEmpty = array_filter($pairs, static fn (string $v): bool => $v !== '');
        if ($nonEmpty === []) {
            return [];
        }

        $out = [self::h('UTM:')];
        foreach ($nonEmpty as $label => $value) {
            $out[] = self::h($label).': '.self::h($value);
        }

        return $out;
    }
}
