<?php

namespace App\Money;

final readonly class CurrencyDefinition
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public int $decimalPlaces,
        public bool $active = true,
        public string $defaultLocale = 'ru_RU',
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            code: strtoupper((string) ($row['code'] ?? '')),
            name: (string) ($row['name'] ?? ''),
            symbol: (string) ($row['symbol'] ?? ''),
            decimalPlaces: max(0, (int) ($row['decimal_places'] ?? 0)),
            active: (bool) ($row['active'] ?? true),
            defaultLocale: (string) ($row['default_locale'] ?? 'ru_RU'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'decimal_places' => $this->decimalPlaces,
            'active' => $this->active,
            'default_locale' => $this->defaultLocale,
        ];
    }
}
