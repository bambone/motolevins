<?php

namespace App\Product\CRM\DTO;

/**
 * Ручное создание обращения из tenant admin (оператор).
 */
final class ManualLeadCreateData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $comment = null,
        public readonly ?string $messenger = null,
        public readonly ?int $motorcycleId = null,
        public readonly ?string $rentalDateFromYmd = null,
        public readonly ?string $rentalDateToYmd = null,
        public readonly bool $createCrm = true,
        public readonly bool $createBooking = false,
        public readonly ?int $rentalUnitId = null,
    ) {}
}
