<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

interface SetupItemsProviderContract
{
    /**
     * @return array<string, SetupItemDefinition>
     */
    public function definitions(): array;
}
