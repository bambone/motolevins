<?php

declare(strict_types=1);

namespace Tests\Unit\MotorcyclePricing;

use App\MotorcyclePricing\ApplicabilityMode;
use App\MotorcyclePricing\MotorcyclePricingProfileValidator;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\TariffKind;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MotorcyclePricingProfileValidatorSaveTest extends TestCase
{
    public function test_blocking_errors_require_on_request_note(): void
    {
        $tid = (string) Str::uuid();
        $profile = $this->baseProfile($tid, note: '');
        $errors = app(MotorcyclePricingProfileValidator::class)->blockingErrorsForSave($profile);
        $this->assertContains('on_request_tariff_requires_note:'.$tid, $errors);
    }

    public function test_blocking_errors_detect_overlapping_duration_ranges(): void
    {
        $a = (string) Str::uuid();
        $b = (string) Str::uuid();
        $profile = [
            'schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'currency' => 'RUB',
            'tariffs' => [
                $this->tariffRange($a, 1, 5),
                $this->tariffRange($b, 3, 7),
            ],
            'display' => [
                'card_primary_tariff_id' => $a,
                'card_secondary_mode' => 'none',
                'card_secondary_text' => '',
                'card_secondary_tariff_id' => null,
            ],
            'financial_terms' => [],
        ];
        $errors = app(MotorcyclePricingProfileValidator::class)->blockingErrorsForSave($profile);
        $this->assertContains('overlapping_duration_ranges:'.$a.':'.$b, $errors);
    }

    private function baseProfile(string $tid, string $note): array
    {
        return [
            'schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'currency' => 'RUB',
            'tariffs' => [[
                'id' => $tid,
                'label' => 'По запросу',
                'kind' => TariffKind::OnRequest->value,
                'note' => $note,
                'applicability' => ['mode' => ApplicabilityMode::Always->value],
                'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
                'priority' => 500,
                'sort_order' => 10,
            ]],
            'display' => [
                'card_primary_tariff_id' => $tid,
                'card_secondary_mode' => 'none',
                'card_secondary_text' => '',
                'card_secondary_tariff_id' => null,
            ],
            'financial_terms' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tariffRange(string $id, int $min, int $max): array
    {
        return [
            'id' => $id,
            'label' => 'R '.$id,
            'kind' => TariffKind::FixedPerDay->value,
            'amount_minor' => 100_00,
            'applicability' => [
                'mode' => ApplicabilityMode::DurationRangeDays->value,
                'min_days' => $min,
                'max_days' => $max,
            ],
            'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
            'priority' => 500,
            'sort_order' => 10,
        ];
    }
}
