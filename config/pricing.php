<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy profile fallback (pre-cutover only)
    |--------------------------------------------------------------------------
    |
    | When true, missing pricing_profile_json is synthesized from legacy columns
    | via LegacyMotorcyclePricingProfileFactory. After cutover and backfill this
    | must be false — runtime fallback is considered a defect.
    |
    */
    'legacy_profile_fallback' => env('PRICING_LEGACY_PROFILE_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | Legacy scalar price fallback (motorcycle daily, transitional)
    |--------------------------------------------------------------------------
    |
    | When MotorcycleQuoteEngine does not return status "ok" (on_request,
    | invalid_profile, etc.), PricingService and public card period UI may still
    | use legacy price_per_day math. Set false after cutover — same rule as
    | legacy_profile_fallback: silent legacy pricing is a defect post-migration.
    |
    */
    'legacy_scalar_price_fallback' => env('PRICING_LEGACY_SCALAR_PRICE_FALLBACK', true),

];
