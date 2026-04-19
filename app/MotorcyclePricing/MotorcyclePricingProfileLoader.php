<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;

final class MotorcyclePricingProfileLoader
{
    /**
     * @return array<string, mixed>
     */
    public function loadOrSynthesize(Motorcycle $motorcycle): array
    {
        $raw = $motorcycle->pricing_profile_json;
        if (is_array($raw) && $raw !== []) {
            return $raw;
        }

        if (! config('pricing.legacy_profile_fallback', true)) {
            return [];
        }

        // TODO(blocker-pre-legacy-column-drop): runtime synthesis must not remain once legacy scalars are removed;
        // empty profile + no factory — callers must treat as invalid / on_request instead of silent legacy math.
        return LegacyMotorcyclePricingProfileFactory::fromMotorcycle($motorcycle);
    }
}
