<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Forms\Components\RichEditor\StateCasts\RichEditorStateCast;

/**
 * Защита от повреждённого состояния RichEditor (TipTap) в Livewire: глубокая/битая вложенность
 * может исчерпать память при {@see RichEditorStateCast}::get().
 */
final class FilamentTipTapDocumentSanitizer
{
    private const int MAX_NODES = 8000;

    private const int MAX_DEPTH = 40;

    /**
     * Нормализует сырое значение поля RichEditor из Livewire (документ или кортеж [документ, …]).
     *
     * @return mixed массив документа / кортежа или исходная строка (HTML)
     */
    public static function sanitizeLivewireState(mixed $state): mixed
    {
        if ($state === null || $state === '' || is_string($state)) {
            return $state;
        }

        if (! is_array($state)) {
            return self::emptyDoc();
        }

        if (array_is_list($state) && $state !== []) {
            $head = $state[0];
            $tail = array_slice($state, 1);
            $cleanHead = self::sanitizeDocOrEmpty($head);

            return [$cleanHead, ...$tail];
        }

        return self::sanitizeDocOrEmpty($state);
    }

    /**
     * @return array<string, mixed>
     */
    private static function sanitizeDocOrEmpty(mixed $maybeDoc): array
    {
        if (! is_array($maybeDoc) || ($maybeDoc['type'] ?? null) !== 'doc') {
            return self::emptyDoc();
        }

        if (! self::isReasonableDoc($maybeDoc)) {
            return self::emptyDoc();
        }

        return $maybeDoc;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyDoc(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'textAlign' => 'start',
                    ],
                    'content' => [],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private static function isReasonableDoc(array $doc): bool
    {
        $nodes = 0;
        $stack = [];
        $content = $doc['content'] ?? null;
        if (! is_array($content)) {
            return false;
        }
        foreach ($content as $child) {
            $stack[] = [$child, 1];
        }

        while ($stack !== []) {
            [$n, $depth] = array_pop($stack);
            if (++$nodes > self::MAX_NODES || $depth > self::MAX_DEPTH) {
                return false;
            }

            if (! is_array($n)) {
                continue;
            }

            $type = $n['type'] ?? null;
            if ($type === 'text') {
                continue;
            }
            if (! is_string($type) || $type === '') {
                return false;
            }

            $attrs = $n['attrs'] ?? null;
            if ($attrs !== null) {
                if (! is_array($attrs)) {
                    return false;
                }
                // ProseMirror attrs are an object; a non-empty JSON list is corrupt / Livewire noise.
                if ($attrs !== [] && array_is_list($attrs)) {
                    return false;
                }
            }

            $childContent = $n['content'] ?? null;
            if ($childContent === null) {
                continue;
            }
            if (! is_array($childContent)) {
                return false;
            }

            foreach ($childContent as $c) {
                $stack[] = [$c, $depth + 1];
            }
        }

        return true;
    }
}
