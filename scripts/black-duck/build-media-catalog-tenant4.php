<?php

/**
 * Сборка media-catalog.json (v3) для тенанта 4: works_gallery + service_gallery на посадочные.
 * Запуск из корня проекта: php scripts/black-duck/build-media-catalog-tenant4.php
 */

declare(strict_types=1);

use App\Tenant\BlackDuck\BlackDuckMediaCatalog;
use App\Tenant\BlackDuck\BlackDuckMediaRole;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = 4;

$works = [
    ['path' => 'site/brand/proof/wg-01.webp', 'service_slug' => 'predprodazhnaya', 'title' => 'Кроссовер: итог', 'summary' => 'Showcase, подготовка к подаче.', 'tags' => ['результат', 'премиум'], 'sort' => 10],
    ['path' => 'site/brand/proof/wg-02.webp', 'service_slug' => 'polirovka-kuzova', 'title' => 'Глубокий блеск', 'summary' => 'Фронтальная подача ЛКП.', 'tags' => ['результат', 'полировка'], 'sort' => 20],
    ['path' => 'site/brand/proof/wg-03.webp', 'service_slug' => 'keramika', 'title' => 'Спорт: профиль', 'summary' => 'Акцент на линиях кузова.', 'tags' => ['результат', 'керамика'], 'sort' => 30],
    ['path' => 'site/brand/proof/wg-04.webp', 'service_slug' => 'detejling-mojka', 'title' => 'Актуальная геометрия', 'summary' => 'Ракурс после комплексного ухода.', 'tags' => ['результат', 'мойка'], 'sort' => 40],
    ['path' => 'site/brand/proof/wg-05.webp', 'service_slug' => 'remont-skolov', 'title' => 'Задняя треть', 'summary' => 'Подача кузова и оптики.', 'tags' => ['результат', 'лак'], 'sort' => 50],
    ['path' => 'site/brand/proof/wg-06.webp', 'service_slug' => 'ppf', 'title' => 'G-class: присутствие', 'summary' => 'Сильный имиджевый кадр.', 'tags' => ['результат', 'PPF'], 'sort' => 60],
    ['path' => 'site/brand/proof/wg-07.webp', 'service_slug' => 'tonirovka', 'title' => 'Компакт: студия', 'summary' => 'Сайд-ракурс в боксе.', 'tags' => ['результат', 'субар'], 'sort' => 70],
    ['path' => 'site/brand/proof/wg-08.webp', 'service_slug' => 'ppf', 'title' => 'Оклейка зоны риска', 'summary' => 'Плёнка на крыло, визуально читаемо.', 'tags' => ['процесс', 'PPF'], 'sort' => 80],
    ['path' => 'site/brand/proof/wg-09.webp', 'service_slug' => 'polirovka-kuzova', 'title' => 'Машинная полировка', 'summary' => 'Контроль пятна и площадки.', 'tags' => ['процесс', 'абразив'], 'sort' => 90],
    ['path' => 'site/brand/proof/wg-10.webp', 'service_slug' => 'pdr', 'title' => 'PDR: подсветка', 'summary' => 'Диагностика вмятины на панели.', 'tags' => ['процесс', 'PDR'], 'sort' => 100],
    ['path' => 'site/brand/proof/wg-11.webp', 'service_slug' => 'himchistka-salona', 'title' => 'Салон: светлое кресло', 'summary' => 'Рабочий кадр по текстилю/коже.', 'tags' => ['процесс', 'салон'], 'sort' => 110],
    ['path' => 'site/brand/proof/wg-12.webp', 'service_slug' => 'vinil', 'title' => 'Крупный формат', 'summary' => 'Натяжение плёнки на панель.', 'tags' => ['процесс', 'винил'], 'sort' => 120],
    ['path' => 'site/brand/proof/wg-13.webp', 'service_slug' => 'tonirovka', 'title' => 'Тонировка стекла', 'summary' => 'Формовка плёнки, кромка.', 'tags' => ['процесс', 'стекла'], 'sort' => 130],
    ['path' => 'site/brand/proof/wg-14.webp', 'service_slug' => 'himchistka-salona', 'title' => 'Карта двери', 'summary' => 'Деталировка пластика/кожи.', 'tags' => ['процесс', 'салон'], 'sort' => 140],
    ['path' => 'site/brand/proof/wg-15.webp', 'service_slug' => 'ppf', 'title' => 'Синяя подложка', 'summary' => 'Расклейка / контроль клея.', 'tags' => ['процесс', 'плёнка'], 'sort' => 150],
    ['path' => 'site/brand/proof/wg-16.webp', 'service_slug' => 'polirovka-kuzova', 'title' => 'Снятие слоя', 'summary' => 'Микроволокно после состава.', 'tags' => ['процесс', 'финиш'], 'sort' => 160],
    ['path' => 'site/brand/proof/wg-17.webp', 'service_slug' => 'kozha-keramika', 'title' => 'Кожа: уход', 'summary' => 'Нанесение на перфорацию.', 'tags' => ['процесс', 'кожа'], 'sort' => 170],
    ['path' => 'site/brand/proof/wg-18.webp', 'service_slug' => 'keramika', 'title' => 'Рефлекс на фоне', 'summary' => 'Контраст кузова, деталь.', 'tags' => ['деталь', 'блик'], 'sort' => 180],
    ['path' => 'site/brand/proof/wg-19.webp', 'service_slug' => 'tonirovka', 'title' => 'Подача борта', 'summary' => 'Сочный отражающий слой.', 'tags' => ['деталь', 'кузов'], 'sort' => 190],
    ['path' => 'site/brand/proof/wg-20.webp', 'service_slug' => 'keramika', 'title' => 'Макро: глянец', 'summary' => 'Глубина и ровность.', 'tags' => ['деталь', 'керамика'], 'sort' => 200],
    ['path' => 'site/brand/proof/wg-21.webp', 'service_slug' => 'pdr', 'title' => 'Колпак: фактура', 'summary' => 'Сервисный макро-кадр.', 'tags' => ['деталь', 'диск'], 'sort' => 210],
    ['path' => 'site/brand/proof/wg-22.webp', 'service_slug' => 'himchistka-salona', 'title' => 'Салон: панель', 'summary' => 'Свет, дефлекторы.', 'tags' => ['деталь', 'интерьер'], 'sort' => 220],
    ['path' => 'site/brand/proof/wg-23.webp', 'service_slug' => 'ppf', 'title' => 'Бокс: свет', 'summary' => 'Панорама зала, контекст работы.', 'tags' => ['студия', 'бокс'], 'sort' => 230],
    ['path' => 'site/brand/proof/wg-24.webp', 'service_slug' => 'keramika', 'title' => 'Пост: разметка', 'summary' => 'Простор бокса, зона смены.', 'tags' => ['студия', 'помещение'], 'sort' => 240],
    ['path' => 'site/brand/proof/wg-25.webp', 'service_slug' => 'polirovka-kuzova', 'title' => 'Станция мастера', 'summary' => 'Оборудование и подготовка.', 'tags' => ['студия', 'инструмент'], 'sort' => 250],
];

$serviceGalleries = [
    ['slug' => 'ppf', 'title' => 'PPF: зона оклейки', 'summary' => 'Плёнка и кромка под ракурс.'],
    ['slug' => 'keramika', 'title' => 'Керамика: глянец', 'summary' => 'Отражение и глубина.'],
    ['slug' => 'polirovka-kuzova', 'title' => 'Полировка: процесс', 'summary' => 'Пятно и абразив под контролем.'],
    ['slug' => 'himchistka-salona', 'title' => 'Салон', 'summary' => 'Светлое кресло, работа с фактурой.'],
    ['slug' => 'predprodazhnaya', 'title' => 'Предпродажа: подача', 'summary' => 'Итоговый внешний вид.'],
    ['slug' => 'pdr', 'title' => 'PDR', 'summary' => 'Подсветка и инструмент.'],
];

$assets = [];
foreach ($works as $w) {
    $raw = [
        'role' => BlackDuckMediaRole::WorksGallery->value,
        'logical_path' => $w['path'],
        'service_slug' => $w['service_slug'],
        'page_slug' => 'raboty',
        'title' => $w['title'],
        'summary' => $w['summary'],
        'tags' => $w['tags'],
        'sort_order' => $w['sort'],
        'show_on_works' => true,
        'is_featured' => $w['sort'] <= 100,
    ];
    $n = BlackDuckMediaCatalog::normalizeAssetRow($raw, $tenantId);
    if ($n === null) {
        fwrite(STDERR, "Skip invalid works row: ".json_encode($w, JSON_UNESCAPED_UNICODE)."\n");
        continue;
    }
    $assets[] = $n;
}

$sgSort = 0;
foreach ($serviceGalleries as $sg) {
    $slug = $sg['slug'];
    $raw = [
        'role' => BlackDuckMediaRole::ServiceGallery->value,
        'logical_path' => 'site/brand/services/'.$slug.'.webp',
        'service_slug' => $slug,
        'title' => $sg['title'],
        'summary' => $sg['summary'],
        'sort_order' => $sgSort++,
        'is_featured' => true,
        'show_on_service' => true,
    ];
    $n = BlackDuckMediaCatalog::normalizeAssetRow($raw, $tenantId);
    if ($n === null) {
        fwrite(STDERR, "Skip invalid service_gallery: ".$slug."\n");
        continue;
    }
    $assets[] = $n;
}

$payload = json_encode([
    'version' => BlackDuckMediaCatalog::SCHEMA_VERSION,
    'assets' => array_values($assets),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($payload === false) {
    echo "JSON encode failed\n";
    exit(1);
}

// loadOrEmpty() uses disk "public" (storage/app/public), not only mirror
$publicCatalog = storage_path('app/public/tenants/'.$tenantId.'/public/site/brand/media-catalog.json');
$dir = dirname($publicCatalog);
if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
    echo "Cannot create: {$dir}\n";
    exit(1);
}
if (file_put_contents($publicCatalog, $payload) === false) {
    echo "Write failed: {$publicCatalog}\n";
    exit(1);
}

$mediaCatalog = 'C:/OSPanel/home/rentbase-media/tenants/'.$tenantId.'/public/site/brand/media-catalog.json';
$mediaDir = dirname($mediaCatalog);
if (is_dir($mediaDir) || (! is_dir($mediaDir) && @mkdir($mediaDir, 0755, true))) {
    @file_put_contents($mediaCatalog, $payload);
}

echo 'OK: assets='.count($assets).', written: '.$publicCatalog."\n";
