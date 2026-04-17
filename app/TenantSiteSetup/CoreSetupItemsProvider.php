<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Базовый реестр шагов (волна 0). Дополнительные {@see SetupItemsProviderContract} подмешиваются агрегатором.
 */
final class CoreSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return SetupItemRegistry::rawDefinitions();
    }
}
