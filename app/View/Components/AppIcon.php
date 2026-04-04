<?php

namespace App\View\Components;

use Illuminate\Support\Facades\File;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Одна иконка из resources/icons/ (Simple Icons / Lucide по файлу). Без CDN и без npm-бандла всей библиотеки.
 *
 * Темы на R2 — это presentation layer; сами SVG лежат в репозитории приложения и деплоятся вместе с кодом.
 */
final class AppIcon extends Component
{
    /**
     * Имя → относительный путь от resources/icons/ (только allowlist).
     *
     * @var array<string, string>
     */
    private const MAP = [
        'telegram' => 'brands/telegram.svg',
        'whatsapp' => 'brands/whatsapp.svg',
        'vk' => 'brands/vk.svg',
        'instagram' => 'brands/instagram.svg',
        'messenger' => 'brands/messenger.svg',
        'viber' => 'brands/viber.svg',
        'max' => 'brands/max.svg',
        'phone' => 'lucide/phone.svg',
        'mail' => 'lucide/mail.svg',
        'link' => 'lucide/link.svg',
        'clipboard-check' => 'lucide/clipboard-check.svg',
        'smartphone' => 'lucide/smartphone.svg',
    ];

    public function __construct(
        public string $name,
        public string $class = 'h-6 w-6',
        public bool $decorative = true,
    ) {}

    public function svgMarkup(): string
    {
        $rel = self::MAP[$this->name] ?? null;
        if ($rel === null || ! is_string($rel)) {
            return '';
        }
        if (preg_match('#\.\.|^/|\\\\#', $rel) === 1) {
            return '';
        }

        $path = resource_path('icons/'.$rel);
        if (! is_file($path) || ! str_ends_with(strtolower($path), '.svg')) {
            return '';
        }

        $raw = File::get($path);
        $raw = preg_replace('/<\?xml[^>]*\?>\s*/i', '', $raw) ?? $raw;

        $attrs = 'class="'.e($this->class).'"';
        if ($this->decorative) {
            $attrs .= ' aria-hidden="true" focusable="false"';
        }

        $html = preg_replace('/<svg\s+/i', '<svg '.$attrs.' ', $raw, 1);
        if (! is_string($html) || $html === $raw) {
            $html = preg_replace('/<svg\s*>/i', '<svg '.$attrs.'>', $raw, 1);
        }
        if (! is_string($html)) {
            return '';
        }

        if ($this->decorative) {
            $html = preg_replace('/\srole="img"/i', '', $html) ?? $html;
        }

        return $html;
    }

    public function render(): View
    {
        return view('components.app-icon', [
            'svgMarkup' => $this->svgMarkup(),
        ]);
    }
}
