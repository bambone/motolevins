<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$c = \App\Tenant\BlackDuck\BlackDuckMediaCatalog::loadOrEmpty(4);
$ts = \App\Support\Storage\TenantStorage::forTrusted(4);
echo 'assets='.count($c['assets'])."\n";
echo 'ppf: '.($ts->existsPublic('site/brand/services/ppf.webp') ? 'yes' : 'no')."\n";
echo 'wg01: '.($ts->existsPublic('site/brand/proof/wg-01.webp') ? 'yes' : 'no')."\n";
echo 'hero: '.($ts->existsPublic('site/brand/hero-1916.webp') ? 'yes' : 'no')."\n";
