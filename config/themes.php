<?php

return [

    /**
     * Ключ темы по умолчанию, если у тенанта пустой/невалидный theme_key.
     * Должен совпадать с каталогом resources/themes/{key}/theme.json.
     */
    'default_key' => env('PLATFORM_DEFAULT_THEME_KEY', 'moto'),

    /**
     * Префикс URL для статики в public (опционально). Источники: resources → /theme/build/…,
     * object storage {@code tenants/_system/themes/{key}/…} (см. theme:push-system-bundled),
     * переопределения клиента {@code tenants/{id}/public/themes/…}.
     */
    'public_asset_root' => 'themes',

    /**
     * Переходный fallback, если файла ещё нет в public/themes/{key}/... и нет в resources/themes/{key}/public.
     * После переноса ассетов в R2 и удаления дубликатов в public ({@see theme:prune-legacy-public}) задайте пустую строку:
     * THEME_LEGACY_ASSET_PREFIX=
     */
    'legacy_asset_url_prefix' => env('THEME_LEGACY_ASSET_PREFIX', 'images/motolevins'),

    /*
    |--------------------------------------------------------------------------
    | Предустановленная тема в object storage (R2/S3 public disk)
    |--------------------------------------------------------------------------
    |
    | Ключи: tenants/_system/themes/{theme_key}/… (см. TenantStorage::systemBundledThemeObjectKey).
    | Пусто/null = авто: да, если публичный диск не локальный Flysystem (например r2-public).
    | false — всегда отдавать из resources → /theme/build/… (dev / пока не залили в бакет).
    |
    */
    'system_theme_use_object_storage' => env('THEME_SYSTEM_THEME_OBJECT_STORAGE'),

];
