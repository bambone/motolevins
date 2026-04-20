<?php

declare(strict_types=1);

namespace App\BookingConsent;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\Request;

/**
 * Динамические правила и флаг «использовать пункты из БД» для снимка согласий.
 */
final class BookingConsentValidationRules
{
    public function __construct(
        private readonly TenantBookingConsentQuery $consentQuery,
        private readonly BookingConsentApplicabilityResolver $applicability,
    ) {}

    /**
     * POST /api/leads: согласия только при сценарии брони (motorcycle_id).
     *
     * @return array<string, mixed>
     */
    public function applyLeadRules(Request $request, array $base): array
    {
        $tenant = \currentTenant();
        if ($tenant === null || ! $this->applicability->isBookingScenarioForLeadRequest($request)) {
            return $base;
        }

        if ($this->dynamicConsentActive($tenant)) {
            return $this->withDynamicConsentRules($base, $tenant);
        }

        return array_merge($base, [
            'agree_to_terms' => ['required', 'accepted'],
            'agree_to_privacy' => ['required', 'accepted'],
        ]);
    }

    /**
     * Публичный checkout бронирования.
     *
     * @return array<string, mixed>
     */
    public function applyCheckoutRules(Request $request, array $base): array
    {
        $tenant = \currentTenant();
        if ($tenant === null) {
            return $base;
        }

        if ($this->dynamicConsentActive($tenant)) {
            return $this->withDynamicConsentRules($base, $tenant);
        }

        return array_merge($base, [
            'agree_to_terms' => ['required', 'accepted'],
            'agree_to_privacy' => ['required', 'accepted'],
        ]);
    }

    public function dynamicConsentActive(Tenant $tenant): bool
    {
        if (! (bool) TenantSetting::getForTenant((int) $tenant->id, 'booking.legal_consents_required', false)) {
            return false;
        }

        return $this->consentQuery->enabledOrdered((int) $tenant->id)->isNotEmpty();
    }

    /**
     * @return array<string, mixed>
     */
    private function withDynamicConsentRules(array $base, Tenant $tenant): array
    {
        $items = $this->consentQuery->enabledOrdered((int) $tenant->id);
        $consentRules = [
            'consent_accepted' => ['nullable', 'array'],
        ];
        foreach ($items as $item) {
            if (! $item->is_required) {
                continue;
            }
            $consentRules['consent_accepted.'.$item->id] = ['accepted'];
        }

        return array_merge($base, $consentRules);
    }
}
