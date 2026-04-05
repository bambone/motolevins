<?php

namespace App\Services\Seo;

/**
 * Deterministic default intro + llms entries for platform marketing (before PlatformSetting overrides).
 */
final class PlatformMarketingLlmsGenerator
{
    /**
     * @return array{intro: string, entries: list<array{path: string, summary: string}>}
     */
    public function generate(): array
    {
        $brand = trim((string) config('platform_marketing.brand_name', 'RentBase'));
        $summary = trim((string) config('platform_marketing.llms_summary', ''));
        $intro = $summary !== '' ? $summary : ($brand.' — маркетинговый сайт платформы. Экспериментальный llms.txt; не заменяет sitemap.xml и HTML.');

        $notes = [
            '/' => 'Главная лендинга, оффер и секции продукта',
            '/features' => 'Возможности платформы',
            '/pricing' => 'Тарифы и модель оплаты',
            '/faq' => 'Частые вопросы',
            '/contact' => 'Контакты и заявка',
            '/for-moto-rental' => 'Вертикаль: прокат мото',
            '/for-car-rental' => 'Вертикаль: прокат авто',
            '/for-services' => 'Вертикаль: сервисы по записи',
        ];

        $entries = [];
        foreach ($this->defaultPaths() as $path) {
            $entries[] = [
                'path' => $path,
                'summary' => $notes[$path] ?? 'Страница маркетингового сайта',
            ];
        }

        $backlog = config('platform_marketing.content_backlog_paths', []);
        $backlog = is_array($backlog) ? $backlog : [];
        foreach ($backlog as $backlogPath) {
            $segment = trim((string) $backlogPath);
            if ($segment !== '') {
                $entries[] = ['path' => $segment, 'summary' => '(планируется)'];
            }
        }

        return ['intro' => $intro, 'entries' => $entries];
    }

    /**
     * @return list<string>
     */
    public function defaultPaths(): array
    {
        $paths = config('platform_marketing.marketing_public_paths', []);
        if (is_array($paths) && $paths !== []) {
            return array_values(array_filter(array_map('strval', $paths), fn (string $p): bool => $p !== ''));
        }

        return ['/', '/features', '/pricing', '/faq', '/contact', '/for-moto-rental', '/for-car-rental', '/for-services'];
    }
}
