<?php

namespace App\Services\Seo;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;

/**
 * Renders /llms.txt for platform marketing: PlatformSetting overrides with config fallback (same shape as tenant).
 */
final class PlatformMarketingLlmsBodyRenderer
{
    public function __construct(
        private PlatformMarketingLlmsGenerator $defaults,
    ) {}

    public function render(Request $request): string
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $brand = trim((string) config('platform_marketing.brand_name', 'RentBase'));

        $lines = [
            '# '.$brand,
            '',
        ];

        $intro = trim((string) PlatformSetting::get('marketing.seo.llms_intro', ''));
        if ($intro !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $intro) as $ln) {
                $lines[] = $ln;
            }
            $lines[] = '';
        } else {
            $fallbackIntro = trim((string) config('platform_marketing.llms_summary', ''));
            if ($fallbackIntro !== '') {
                foreach (preg_split("/\r\n|\n|\r/", $fallbackIntro) as $ln) {
                    $lines[] = $ln;
                }
                $lines[] = '';
            } else {
                $label = $brand !== '' ? $brand : 'Сайт';
                $lines[] = $label.' — маркетинговый сайт платформы. Экспериментальный llms.txt; не заменяет sitemap.xml и HTML.';
                $lines[] = '';
            }
        }

        $lines[] = '## Полезные страницы';

        $entriesRaw = PlatformSetting::get('marketing.seo.llms_entries', '');
        $entries = [];
        if (is_string($entriesRaw) && trim($entriesRaw) !== '') {
            $decoded = json_decode($entriesRaw, true);
            if (is_array($decoded)) {
                $entries = $decoded;
            }
        }

        if ($entries !== []) {
            foreach ($entries as $e) {
                if (! is_array($e)) {
                    continue;
                }
                $path = trim((string) ($e['path'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $summary = trim((string) ($e['summary'] ?? ''));
                $url = $path === '/' ? $base.'/' : $base.$path;
                if ($summary !== '') {
                    $lines[] = '- '.$url.' — '.$summary;
                } else {
                    $lines[] = '- '.$url;
                }
            }
        } else {
            foreach ($this->defaults->generate()['entries'] as $row) {
                $path = trim((string) ($row['path'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $summary = trim((string) ($row['summary'] ?? ''));
                $url = $path === '/' ? $base.'/' : $base.$path;
                if ($summary !== '') {
                    $lines[] = '- '.$url.' — '.$summary;
                } else {
                    $lines[] = '- '.$url;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$base.'/sitemap.xml';
        $lines[] = '';
        $lines[] = 'Экспериментальный файл llms.txt; не заменяет sitemap.xml и HTML.';

        return implode("\n", $lines);
    }
}
