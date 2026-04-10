<?php

namespace App\ContactChannels;

use App\Models\Booking;
use App\Models\Lead;
use App\Support\Phone\IntlPhoneNormalizer;

/**
 * Быстрые действия связи: пересечение tenant uses_channel × данные JSON (или только телефон для WA/call).
 */
final class LeadContactActionResolver
{
    public function __construct(
        private readonly TenantContactChannelsStore $tenantChannels,
    ) {}

    /**
     * @return list<array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    public function orderedActionsForLead(Lead $lead): array
    {
        $json = $lead->visitor_contact_channels_json;
        if (! is_array($json) || $json === []) {
            return $this->legacyPhoneOnlyActions($lead);
        }

        $state = $this->tenantChannels->resolvedState((int) $lead->tenant_id);
        $byType = $this->collectDescriptorsFromJson($lead, $json, $state);
        $preferred = (string) ($lead->preferred_contact_channel ?? ContactChannelType::Phone->value);

        return $this->orderDescriptors($byType, $preferred, $state);
    }

    /**
     * @return list<array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    public function orderedActionsForBooking(Booking $booking): array
    {
        $json = $booking->visitor_contact_channels_json;
        if (! is_array($json) || $json === []) {
            return $this->legacyBookingPhoneActions($booking);
        }

        $state = $this->tenantChannels->resolvedState((int) $booking->tenant_id);
        $byType = $this->collectDescriptorsFromBooking($booking, $json, $state);
        $preferred = (string) ($booking->preferred_contact_channel ?? ContactChannelType::Phone->value);

        return $this->orderDescriptors($byType, $preferred, $state);
    }

    /**
     * Компактная строка для таблицы (телефон + preferred + подписи).
     *
     * @param  bool  $includePhone  если false — без первой строки с номером (для description под колонкой «Телефон»).
     */
    public function compactSummaryForLead(Lead $lead, bool $includePhone = true): string
    {
        $phone = trim((string) $lead->phone);
        $phoneDisp = $phone !== '' ? $phone : '—';
        $pref = (string) ($lead->preferred_contact_channel ?? '');
        $prefLabel = $pref !== '' && $pref !== ContactChannelType::Phone->value
            ? ContactChannelRegistry::label($pref)
            : null;

        $lines = [];
        if ($includePhone) {
            $lines[] = $phoneDisp;
        }
        if ($prefLabel !== null) {
            $lines[] = '★ '.$prefLabel;
        }

        $json = $lead->visitor_contact_channels_json;
        if (is_array($json)) {
            foreach ($json as $row) {
                if (! is_array($row) || ! isset($row['type'], $row['value'])) {
                    continue;
                }
                $t = (string) $row['type'];
                if ($t === ContactChannelType::Phone->value) {
                    continue;
                }
                $v = (string) $row['value'];
                if ($t === ContactChannelType::Telegram->value) {
                    $lines[] = 'Telegram: @'.$v;
                } elseif ($t === ContactChannelType::Vk->value) {
                    $lines[] = 'VK: '.$v;
                } elseif ($t === ContactChannelType::Max->value) {
                    $lines[] = 'MAX: '.$v;
                }
            }
        }

        return implode("\n", array_unique($lines));
    }

    /**
     * @param  array<string, array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>  $byType
     * @param  array<string, TenantContactChannelConfig>  $state
     * @return list<array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function orderDescriptors(array $byType, string $preferred, array $state): array
    {
        $ordered = [];
        $added = [];

        if (isset($byType['call'])) {
            $d = $byType['call'];
            $d['is_preferred'] = $preferred === ContactChannelType::Phone->value;
            $ordered[] = $d;
            $added['call'] = true;
        }

        if ($preferred !== ContactChannelType::Phone->value && isset($byType[$preferred])) {
            $d = $byType[$preferred];
            $d['is_preferred'] = true;
            $ordered[] = $d;
            $added[$preferred] = true;
        }

        $remaining = array_diff_key($byType, $added);
        uksort($remaining, function (string $a, string $b) use ($state): int {
            $rank = function (string $k) use ($state): int {
                if ($k === 'call') {
                    return 0;
                }

                return $state[$k]->sortOrder ?? 99;
            };

            $cmp = $rank($a) <=> $rank($b);

            return $cmp !== 0 ? $cmp : $a <=> $b;
        });

        foreach ($remaining as $key => $d) {
            $d['is_preferred'] = false;
            $ordered[] = $d;
        }

        return $ordered;
    }

    /**
     * @param  list<array<string, mixed>>  $json
     * @param  array<string, TenantContactChannelConfig>  $state
     * @return array<string, array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function collectDescriptorsFromJson(Lead $lead, array $json, array $state): array
    {
        $phone = null;
        foreach ($json as $row) {
            if (! is_array($row) || ($row['type'] ?? null) !== ContactChannelType::Phone->value) {
                continue;
            }
            $phone = (string) ($row['value'] ?? '');
            break;
        }
        if ($phone === null || $phone === '') {
            $phone = IntlPhoneNormalizer::normalizePhone((string) $lead->phone);
        }

        return $this->buildDescriptorMap($phone, $json, $state, (string) ($lead->preferred_contact_channel ?? ContactChannelType::Phone->value));
    }

    /**
     * @param  list<array<string, mixed>>  $json
     * @param  array<string, TenantContactChannelConfig>  $state
     * @return array<string, array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function collectDescriptorsFromBooking(Booking $booking, array $json, array $state): array
    {
        $phone = null;
        foreach ($json as $row) {
            if (! is_array($row) || ($row['type'] ?? null) !== ContactChannelType::Phone->value) {
                continue;
            }
            $phone = (string) ($row['value'] ?? '');
            break;
        }
        if ($phone === null || $phone === '') {
            $phone = IntlPhoneNormalizer::normalizePhone((string) $booking->phone);
        }

        return $this->buildDescriptorMap($phone, $json, $state, (string) ($booking->preferred_contact_channel ?? ContactChannelType::Phone->value));
    }

    /**
     * @param  list<array<string, mixed>>  $json
     * @param  array<string, TenantContactChannelConfig>  $state
     * @return array<string, array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function buildDescriptorMap(string $phone, array $json, array $state, string $preferred): array
    {
        $defs = ContactChannelRegistry::definitions();
        $byType = [];

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($phone !== '' && IntlPhoneNormalizer::validatePhone($phone) && $digits !== '') {
            $byType['call'] = [
                'type' => 'call',
                'label' => 'Позвонить',
                'url' => 'tel:'.$digits,
                'icon' => $defs[ContactChannelType::Phone->value]['icon'],
                'color' => $defs[ContactChannelType::Phone->value]['filament_action_color'],
                'open_in_new_tab' => false,
                'is_preferred' => $preferred === ContactChannelType::Phone->value,
                'tooltip' => 'Позвонить',
            ];
        }

        $waCfg = $state[ContactChannelType::Whatsapp->value] ?? null;
        if ($waCfg?->usesChannel && $digits !== '' && strlen($digits) >= 10) {
            $byType[ContactChannelType::Whatsapp->value] = [
                'type' => ContactChannelType::Whatsapp->value,
                'label' => 'WhatsApp',
                'url' => 'https://wa.me/'.$digits.'?text='.rawurlencode('Здравствуйте! Пишу по поводу вашей заявки…'),
                'icon' => $defs[ContactChannelType::Whatsapp->value]['icon'],
                'color' => $defs[ContactChannelType::Whatsapp->value]['filament_action_color'],
                'open_in_new_tab' => true,
                'is_preferred' => $preferred === ContactChannelType::Whatsapp->value,
                'tooltip' => 'Написать в WhatsApp',
            ];
        }

        foreach ($json as $row) {
            if (! is_array($row) || ! isset($row['type'], $row['value'])) {
                continue;
            }
            $t = (string) $row['type'];
            $v = (string) $row['value'];
            if ($t === ContactChannelType::Phone->value || $t === ContactChannelType::Whatsapp->value) {
                continue;
            }

            $cfg = $state[$t] ?? null;
            if ($cfg === null || ! $cfg->usesChannel) {
                continue;
            }

            if ($t === ContactChannelType::Telegram->value) {
                $byType[$t] = [
                    'type' => $t,
                    'label' => 'Telegram',
                    'url' => 'https://t.me/'.$v,
                    'icon' => $defs[$t]['icon'],
                    'color' => $defs[$t]['filament_action_color'],
                    'open_in_new_tab' => true,
                    'is_preferred' => $preferred === $t,
                    'tooltip' => 'Открыть Telegram: @'.$v,
                ];
            } elseif ($t === ContactChannelType::Vk->value) {
                $byType[$t] = [
                    'type' => $t,
                    'label' => 'VK',
                    'url' => $v,
                    'icon' => $defs[$t]['icon'],
                    'color' => $defs[$t]['filament_action_color'],
                    'open_in_new_tab' => true,
                    'is_preferred' => $preferred === $t,
                    'tooltip' => 'Открыть профиль VK',
                ];
            } elseif ($t === ContactChannelType::Max->value) {
                if (! filter_var($v, FILTER_VALIDATE_URL)) {
                    continue;
                }
                $byType[$t] = [
                    'type' => $t,
                    'label' => 'MAX',
                    'url' => $v,
                    'icon' => $defs[$t]['icon'],
                    'color' => $defs[$t]['filament_action_color'],
                    'open_in_new_tab' => true,
                    'is_preferred' => $preferred === $t,
                    'tooltip' => 'MAX',
                ];
            }
        }

        return $byType;
    }

    /**
     * @return list<array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function legacyPhoneOnlyActions(Lead $lead): array
    {
        $phone = IntlPhoneNormalizer::normalizePhone((string) $lead->phone);
        $state = $this->tenantChannels->resolvedState((int) $lead->tenant_id);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $byType = [];

        $defs = ContactChannelRegistry::definitions();
        if ($phone !== '' && IntlPhoneNormalizer::validatePhone($phone) && $digits !== '') {
            $byType['call'] = [
                'type' => 'call',
                'label' => 'Позвонить',
                'url' => 'tel:'.$digits,
                'icon' => $defs[ContactChannelType::Phone->value]['icon'],
                'color' => $defs[ContactChannelType::Phone->value]['filament_action_color'],
                'open_in_new_tab' => false,
                'is_preferred' => true,
                'tooltip' => 'Позвонить',
            ];
        }

        $waCfg = $state[ContactChannelType::Whatsapp->value] ?? null;
        if ($waCfg?->usesChannel && $digits !== '' && strlen($digits) >= 10) {
            $byType[ContactChannelType::Whatsapp->value] = [
                'type' => ContactChannelType::Whatsapp->value,
                'label' => 'WhatsApp',
                'url' => 'https://wa.me/'.$digits.'?text='.rawurlencode('Здравствуйте! Пишу по поводу вашей заявки…'),
                'icon' => $defs[ContactChannelType::Whatsapp->value]['icon'],
                'color' => $defs[ContactChannelType::Whatsapp->value]['filament_action_color'],
                'open_in_new_tab' => true,
                'is_preferred' => false,
                'tooltip' => 'Написать в WhatsApp',
            ];
        }

        return array_values($byType);
    }

    /**
     * @return list<array{type: string, label: string, url: string, icon: string, color: string, open_in_new_tab: bool, is_preferred: bool, tooltip: string}>
     */
    private function legacyBookingPhoneActions(Booking $booking): array
    {
        $phone = IntlPhoneNormalizer::normalizePhone((string) $booking->phone);
        $state = $this->tenantChannels->resolvedState((int) $booking->tenant_id);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $byType = [];
        $defs = ContactChannelRegistry::definitions();

        if ($phone !== '' && IntlPhoneNormalizer::validatePhone($phone) && $digits !== '') {
            $byType['call'] = [
                'type' => 'call',
                'label' => 'Позвонить',
                'url' => 'tel:'.$digits,
                'icon' => $defs[ContactChannelType::Phone->value]['icon'],
                'color' => $defs[ContactChannelType::Phone->value]['filament_action_color'],
                'open_in_new_tab' => false,
                'is_preferred' => true,
                'tooltip' => 'Позвонить',
            ];
        }

        $waCfg = $state[ContactChannelType::Whatsapp->value] ?? null;
        if ($waCfg?->usesChannel && $digits !== '' && strlen($digits) >= 10) {
            $byType[ContactChannelType::Whatsapp->value] = [
                'type' => ContactChannelType::Whatsapp->value,
                'label' => 'WhatsApp',
                'url' => 'https://wa.me/'.$digits.'?text='.rawurlencode('Здравствуйте! Пишу по поводу бронирования…'),
                'icon' => $defs[ContactChannelType::Whatsapp->value]['icon'],
                'color' => $defs[ContactChannelType::Whatsapp->value]['filament_action_color'],
                'open_in_new_tab' => true,
                'is_preferred' => false,
                'tooltip' => 'Написать в WhatsApp',
            ];
        }

        return array_values($byType);
    }
}
