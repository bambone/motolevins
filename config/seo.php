<?php

return [

    'disk' => env('SEO_FILES_DISK', 'local'),

    'sitemap_stale_after_days_default' => (int) env('SEO_SITEMAP_STALE_AFTER_DAYS', 7),

    /*
    | Optional absolute https URL for tenant public OG:image when entity and SeoMeta omit it.
    | Prefer per-tenant TenantSetting `seo.default_og_image_url` in production.
    */
    'tenant_public_fallback_og_image_url' => env('TENANT_PUBLIC_FALLBACK_OG_IMAGE_URL', ''),
];
