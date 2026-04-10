<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\BookableService;
use App\Scheduling\Enums\BookableServiceSettingsApplyMode;

/**
 * Whitelist for preset payload and linked-form payload mapping.
 */
final class BookableServiceSettingsMapper
{
    /** @var list<string> */
    public const ALLOWED_KEYS = [
        'duration_minutes',
        'slot_step_minutes',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'min_booking_notice_minutes',
        'max_booking_horizon_days',
        'requires_confirmation',
        'sort_weight',
        'sync_title_from_source',
    ];

    /**
     * Schema defaults aligned with bookable_services migration / LinkedBookableServiceManager::createLinked*.
     *
     * @var array<string, mixed>
     */
    private const DEFAULTS = [
        'duration_minutes' => 60,
        'slot_step_minutes' => 15,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'min_booking_notice_minutes' => 120,
        'max_booking_horizon_days' => 60,
        'requires_confirmation' => true,
        'sort_weight' => 0,
        'sync_title_from_source' => true,
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function extractWhitelisted(array $payload): array
    {
        $out = [];
        foreach (self::ALLOWED_KEYS as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            $out[$key] = $payload[$key];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $whitelisted  from {@see extractWhitelisted()}
     * @return array<string, mixed>
     */
    public function toLinkedPayload(array $whitelisted): array
    {
        $map = [
            'duration_minutes' => 'linked_duration_minutes',
            'slot_step_minutes' => 'linked_slot_step_minutes',
            'buffer_before_minutes' => 'linked_buffer_before_minutes',
            'buffer_after_minutes' => 'linked_buffer_after_minutes',
            'min_booking_notice_minutes' => 'linked_min_booking_notice_minutes',
            'max_booking_horizon_days' => 'linked_max_booking_horizon_days',
            'requires_confirmation' => 'linked_requires_confirmation',
            'sort_weight' => 'linked_sort_weight',
            'sync_title_from_source' => 'linked_sync_title_from_source',
        ];
        $out = [];
        foreach ($whitelisted as $k => $v) {
            if (isset($map[$k])) {
                $out[$map[$k]] = $v;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $whitelisted
     * @return array<string, mixed> normalized types for BookableService columns
     */
    public function normalizeForBookableService(array $whitelisted): array
    {
        $out = [];
        if (array_key_exists('duration_minutes', $whitelisted)) {
            $out['duration_minutes'] = (int) $whitelisted['duration_minutes'];
        }
        if (array_key_exists('slot_step_minutes', $whitelisted)) {
            $out['slot_step_minutes'] = (int) $whitelisted['slot_step_minutes'];
        }
        if (array_key_exists('buffer_before_minutes', $whitelisted)) {
            $out['buffer_before_minutes'] = (int) $whitelisted['buffer_before_minutes'];
        }
        if (array_key_exists('buffer_after_minutes', $whitelisted)) {
            $out['buffer_after_minutes'] = (int) $whitelisted['buffer_after_minutes'];
        }
        if (array_key_exists('min_booking_notice_minutes', $whitelisted)) {
            $out['min_booking_notice_minutes'] = (int) $whitelisted['min_booking_notice_minutes'];
        }
        if (array_key_exists('max_booking_horizon_days', $whitelisted)) {
            $out['max_booking_horizon_days'] = (int) $whitelisted['max_booking_horizon_days'];
        }
        if (array_key_exists('requires_confirmation', $whitelisted)) {
            $out['requires_confirmation'] = (bool) $whitelisted['requires_confirmation'];
        }
        if (array_key_exists('sort_weight', $whitelisted)) {
            $out['sort_weight'] = (int) $whitelisted['sort_weight'];
        }
        if (array_key_exists('sync_title_from_source', $whitelisted)) {
            $out['sync_title_from_source'] = (bool) $whitelisted['sync_title_from_source'];
        }

        return $out;
    }

    /**
     * Filters preset values against a service when using FillMissingOnly.
     *
     * @param  array<string, mixed>  $whitelisted
     * @return array<string, mixed>
     */
    public function filterForApplyMode(
        BookableService $service,
        array $whitelisted,
        BookableServiceSettingsApplyMode $mode,
    ): array {
        if ($mode === BookableServiceSettingsApplyMode::Replace) {
            return $whitelisted;
        }

        $filtered = [];
        foreach ($whitelisted as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            $default = self::DEFAULTS[$key];
            $current = $service->getAttribute($key);
            if ($this->valueMatchesDefault($current, $default)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function valueMatchesDefault(mixed $current, mixed $default): bool
    {
        if (is_bool($default)) {
            return (bool) $current === $default;
        }

        return (int) $current === (int) $default;
    }
}
