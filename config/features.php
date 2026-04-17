<?php

return [
    /**
     * Tenant admin: readiness widget, setup center, guided overlay, setup sessions.
     * When false: hide Filament UI; overlay hooks do not render (state rows may remain).
     */
    'tenant_site_setup_framework' => (bool) env('FEATURE_TENANT_SITE_SETUP', true),
];
