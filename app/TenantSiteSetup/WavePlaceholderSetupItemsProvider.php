<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Зарезервировано под будущие волны пунктов (P2): волна 1–3 из плана — отдельные провайдеры.
 */
final class WavePlaceholderSetupItemsProvider implements SetupItemsProviderContract
{
    public function definitions(): array
    {
        return [];
    }
}
