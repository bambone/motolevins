<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformLlmsTxtController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $brand = (string) config('platform_marketing.brand_name', 'RentBase');
        $summary = trim((string) config('platform_marketing.llms_summary', ''));

        $lines = [
            '# '.$brand,
            '',
            $summary,
            '',
            '## Pages',
        ];

        $pages = [
            ['path' => '/', 'note' => 'Главная лендинга, оффер и секции продукта'],
            ['path' => '/features', 'note' => 'Возможности платформы'],
            ['path' => '/pricing', 'note' => 'Тарифы и модель оплаты'],
            ['path' => '/faq', 'note' => 'Частые вопросы'],
            ['path' => '/contact', 'note' => 'Контакты и заявка'],
            ['path' => '/for-moto-rental', 'note' => 'Вертикаль: прокат мото'],
            ['path' => '/for-car-rental', 'note' => 'Вертикаль: прокат авто'],
        ];

        foreach ($pages as $p) {
            $lines[] = '- '.$base.$p['path'].' — '.$p['note'];
        }

        $backlogPaths = config('platform_marketing.content_backlog_paths', []);
        $backlogPaths = is_array($backlogPaths) ? $backlogPaths : [];

        foreach ($backlogPaths as $backlogPath) {
            $segment = trim((string) $backlogPath);
            if ($segment !== '') {
                $lines[] = '- '.$base.$segment.' — (планируется)';
            }
        }

        $lines[] = '';
        $lines[] = 'Экспериментальный файл llms.txt; не заменяет sitemap.xml и HTML.';

        $body = implode("\n", $lines);

        $response = new Response($body, 200);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
