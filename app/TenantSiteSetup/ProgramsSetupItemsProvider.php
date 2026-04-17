<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * P2: программы / услуги — волны расширения реестра; пока пусто.
 */
final class ProgramsSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [];
    }
}
