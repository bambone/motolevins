<?php

namespace App\Support;

/**
 * Публичный вывод ответа FAQ: в БД может быть и plain text, и HTML (см. BlackDuckContentRefresher, Filament).
 * Тот же контракт, что в `resources/views/tenant/themes/expert_auto/sections/faq.blade.php`:
 * HTML определяется эвристикой тега; иначе — escape + nl2br в шаблоне.
 */
final class FaqAnswerForPublicView
{
    /**
     * @return array{is_html: bool, body: string} body готов к {!! $body !!}: plain — уже e(); HTML — пропущен через strip_tags.
     */
    public static function fromStoredAnswer(string $raw): array
    {
        $raw = (string) $raw;
        $answerLooksLikeHtml = preg_match('/<[a-z][\s\S]*>/i', $raw) === 1;
        if ($answerLooksLikeHtml) {
            $html = strip_tags($raw, '<p><br><a><strong><em><b><i><u><ul><ol><li><span>');

            return [
                'is_html' => true,
                'body' => self::stripUnsafeAttributes($html),
            ];
        }

        return [
            'is_html' => false,
            'body' => e($raw),
        ];
    }

    /**
     * strip_tags оставляет атрибуты на разрешённых тегах — убираем on* и javascript: в href.
     */
    private static function stripUnsafeAttributes(string $html): string
    {
        if ($html === '') {
            return $html;
        }
        $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*(["\'])\s*javascript:/iu', 'href=$1blocked:', $html) ?? $html;

        return $html;
    }
}
