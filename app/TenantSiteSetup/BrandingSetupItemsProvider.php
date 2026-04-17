<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * P2: дорожка брендинга — дополнительные пункты (волна 1+); пока пусто, подмешивается агрегатором.
 */
final class BrandingSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [];
    }
}
