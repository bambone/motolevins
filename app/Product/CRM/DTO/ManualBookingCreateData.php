<?php

namespace App\Product\CRM\DTO;

/**
 * Ручное создание бронирования из tenant admin.
 */
final class ManualBookingCreateData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly int $motorcycleId,
        public readonly int $rentalUnitId,
        public readonly string $startDateYmd,
        public readonly string $endDateYmd,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $comment = null,
        public readonly ?int $existingLeadId = null,
        public readonly bool $createLead = true,
        public readonly bool $createCrm = true,
    ) {}
}
