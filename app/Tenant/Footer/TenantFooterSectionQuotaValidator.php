<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

use App\Models\TenantFooterSection;
use Illuminate\Validation\ValidationException;

/**
 * Кратность типов и общий лимит секций на тенанта (v1).
 */
final class TenantFooterSectionQuotaValidator
{
    /**
     * @throws ValidationException
     */
    public function validateForSave(int $tenantId, string $type, bool $willBeEnabled, ?int $ignoreSectionId = null): void
    {
        if (! $willBeEnabled) {
            return;
        }

        $maxForType = FooterSectionType::maxPerType($type);
        $sameType = TenantFooterSection::query()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_enabled', true)
            ->when($ignoreSectionId !== null, fn ($q) => $q->whereKeyNot($ignoreSectionId))
            ->count();
        if ($sameType >= $maxForType) {
            throw ValidationException::withMessages([
                'type' => 'Достигнут лимит активных секций этого типа ('.$maxForType.').',
            ]);
        }

        $total = TenantFooterSection::query()
            ->where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->when($ignoreSectionId !== null, fn ($q) => $q->whereKeyNot($ignoreSectionId))
            ->count();
        if ($total >= FooterLimits::MAX_SECTIONS_TOTAL) {
            throw ValidationException::withMessages([
                'type' => 'Максимум '.FooterLimits::MAX_SECTIONS_TOTAL.' активных секций подвала на сайт.',
            ]);
        }
    }
}
