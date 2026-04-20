<?php

declare(strict_types=1);

namespace App\BookingConsent;

use Illuminate\Http\Request;

/**
 * Единая классификация сценария «бронь/аренда» для показа и валидации согласий.
 */
final class BookingConsentApplicabilityResolver
{
    /**
     * Публичный checkout бронирования и POST /api/leads с контекстом брони (motorcycle_id).
     */
    public function isBookingScenario(Request $request): bool
    {
        $name = $request->route()?->getName();

        if ($name === 'booking.store-checkout') {
            return true;
        }

        if ($name === 'api.leads.store') {
            return $this->leadRequestLooksLikeBooking($request);
        }

        return false;
    }

    public function isBookingScenarioForLeadRequest(Request $request): bool
    {
        return $this->leadRequestLooksLikeBooking($request);
    }

    private function leadRequestLooksLikeBooking(Request $request): bool
    {
        $raw = $request->input('motorcycle_id');

        return $raw !== null && $raw !== '' && (int) $raw > 0;
    }
}
