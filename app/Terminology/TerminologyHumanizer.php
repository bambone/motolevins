<?php

namespace App\Terminology;

use Illuminate\Support\Str;

/**
 * Emergency display strings when a term_key must be shown without a reliable DB default_label.
 * Known keys resolve to Russian via {@see DomainTermEmergencyLabels}; unknown keys use Latin headline.
 */
final class TerminologyHumanizer
{
    public static function humanize(string $termKey): string
    {
        $termKey = trim($termKey);
        if ($termKey === '') {
            return '';
        }

        $ru = DomainTermEmergencyLabels::ruOrNull($termKey);
        if ($ru !== null) {
            return $ru;
        }

        return Str::headline(str_replace(['.', '_'], ' ', $termKey));
    }
}
