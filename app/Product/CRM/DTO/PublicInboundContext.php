<?php

namespace App\Product\CRM\DTO;

final class PublicInboundContext
{
    public function __construct(
        public readonly bool $isPlatformScope,
        public readonly ?int $tenantId,
    ) {}

    public static function platform(): self
    {
        return new self(isPlatformScope: true, tenantId: null);
    }

    public static function tenant(int $tenantId): self
    {
        return new self(isPlatformScope: false, tenantId: $tenantId);
    }
}
