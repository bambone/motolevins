<?php

declare(strict_types=1);

return [
    /*
    | Релиз 1: при пустом body читать legacy (text_long → text_short → text).
    | Релиз 2: установите REVIEWS_BODY_READ_FALLBACK=false после backfill на проде.
    */
    'body_read_fallback' => filter_var(
        env('REVIEWS_BODY_READ_FALLBACK', true),
        FILTER_VALIDATE_BOOL,
    ),

    'import' => [
        'queue' => env('REVIEWS_IMPORT_QUEUE', 'default'),
        'timeout' => (int) env('REVIEWS_IMPORT_TIMEOUT', 60),
        'max_per_run' => (int) env('REVIEWS_IMPORT_MAX_PER_RUN', 100),
        'min_text_length' => (int) env('REVIEWS_IMPORT_MIN_TEXT_LENGTH', 30),
        'vk_page_size' => (int) env('REVIEWS_IMPORT_VK_PAGE_SIZE', 100),
        'vk_max_pages_per_run' => (int) env('REVIEWS_IMPORT_VK_MAX_PAGES', 5),
        'download_avatars' => filter_var(env('REVIEWS_IMPORT_DOWNLOAD_AVATARS', true), FILTER_VALIDATE_BOOL),
    ],

    'public' => [
        'show_source_links' => filter_var(env('REVIEWS_PUBLIC_SHOW_SOURCE_LINKS', false), FILTER_VALIDATE_BOOL),
    ],
];
