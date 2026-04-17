<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

/**
 * Собирает карту {@see SetupItemDefinition} из нескольких провайдеров (последующий ключ перезаписывает предыдущий).
 */
final class SetupItemsAggregator
{
    /**
     * @param  iterable<SetupItemsProviderContract>  $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    /**
     * @return array<string, SetupItemDefinition>
     */
    public function merge(): array
    {
        $map = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->definitions() as $key => $def) {
                $map[$key] = $def;
            }
        }

        return $map;
    }
}
