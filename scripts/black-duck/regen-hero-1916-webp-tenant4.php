<?php

declare(strict_types=1);

/**
 * Rebuild site/brand/hero-1916.webp from hero-1916.jpg (fixes wrong file e.g. copied from proof).
 */
$src = 'C:/OSPanel/home/rentbase-media/tenants/4/public/site/brand/hero-1916.jpg';
$destinations = [
    'C:/OSPanel/home/rentbase-media/tenants/4/public/site/brand/hero-1916.webp',
    __DIR__.'/../../storage/app/public/tenants/4/public/site/brand/hero-1916.webp',
];

if (! is_file($src)) {
    fwrite(STDERR, "Missing source: {$src}\n");
    exit(1);
}

$im = imagecreatefromjpeg($src);
if ($im === false) {
    fwrite(STDERR, "Failed to read JPEG: {$src}\n");
    exit(1);
}

foreach ($destinations as $d) {
    $dir = dirname($d);
    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
        fwrite(STDERR, "Cannot mkdir: {$dir}\n");
        exit(1);
    }
    if (! imagewebp($im, $d, 90)) {
        imagedestroy($im);
        fwrite(STDERR, "Failed to write: {$d}\n");
        exit(1);
    }
    fwrite(STDOUT, "Wrote {$d}\n");
}

imagedestroy($im);
fwrite(STDOUT, "OK\n");
exit(0);
