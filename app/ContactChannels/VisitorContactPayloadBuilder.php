<?php

namespace App\ContactChannels;

use App\Support\Phone\IntlPhoneNormalizer;
use Illuminate\Validation\ValidationException;

/**
 * Единый билдер: phone в JSON всегда + preferred_* синхронно с массивом.
 */
final class VisitorContactPayloadBuilder
{
    public function __construct(
        private readonly TenantContactChannelsStore $tenantChannels,
    ) {}

    /**
     * @param  array{phone: string, preferred_contact_channel: string, preferred_contact_value?: ?string}  $input
     * @return array{preferred_contact_channel: string, preferred_contact_value: ?string, visitor_contact_channels_json: list<array<string, mixed>>}
     */
    public function build(int $tenantId, array $input): array
    {
        $phone = IntlPhoneNormalizer::normalizePhone($input['phone'] ?? '');
        if ($phone === '' || ! IntlPhoneNormalizer::validatePhone($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Укажите корректный телефон в международном формате.',
            ]);
        }

        $preferred = (string) ($input['preferred_contact_channel'] ?? ContactChannelType::Phone->value);
        $allowed = $this->tenantChannels->allowedPreferredChannelIds($tenantId);
        if (! in_array($preferred, $allowed, true)) {
            throw ValidationException::withMessages([
                'preferred_contact_channel' => 'Выбран недопустимый способ связи.',
            ]);
        }

        $extraRaw = isset($input['preferred_contact_value']) ? trim((string) $input['preferred_contact_value']) : '';

        if (ContactChannelRegistry::requiresVisitorValue($preferred)) {
            if ($extraRaw === '') {
                throw ValidationException::withMessages([
                    'preferred_contact_value' => PreferredContactValueMessages::requiredRu($preferred),
                ]);
            }
        }

        $channels = [];

        $channels[] = [
            'type' => ContactChannelType::Phone->value,
            'value' => $phone,
        ];

        $preferredValue = null;

        if ($preferred === ContactChannelType::Phone->value) {
            $preferredValue = $phone;
        } elseif ($preferred === ContactChannelType::Whatsapp->value) {
            $preferredValue = $phone;
            $channels[] = [
                'type' => ContactChannelType::Whatsapp->value,
                'value' => $phone,
                'meta' => ['uses_same_phone' => true],
            ];
        } elseif ($preferred === ContactChannelType::Telegram->value) {
            $u = VisitorContactNormalizer::normalizeTelegram($extraRaw);
            if ($u === null) {
                throw ValidationException::withMessages([
                    'preferred_contact_value' => PreferredContactValueMessages::invalidFormatRu($preferred),
                ]);
            }
            $preferredValue = $u;
            $row = [
                'type' => ContactChannelType::Telegram->value,
                'value' => $u,
            ];
            if ($extraRaw !== '' && $extraRaw !== $u && '@'.$u !== $extraRaw && 't.me/'.$u !== strtolower($extraRaw)) {
                $row['raw_value'] = $extraRaw;
            }
            $channels[] = $row;
        } elseif ($preferred === ContactChannelType::Vk->value) {
            $url = VisitorContactNormalizer::normalizeVk($extraRaw);
            if ($url === null) {
                throw ValidationException::withMessages([
                    'preferred_contact_value' => PreferredContactValueMessages::invalidFormatRu($preferred),
                ]);
            }
            $preferredValue = $url;
            $row = ['type' => ContactChannelType::Vk->value, 'value' => $url];
            if ($extraRaw !== $url) {
                $row['raw_value'] = $extraRaw;
            }
            $channels[] = $row;
        } elseif ($preferred === ContactChannelType::Max->value) {
            $v = VisitorContactNormalizer::normalizeMax($extraRaw);
            if ($v === null) {
                throw ValidationException::withMessages([
                    'preferred_contact_value' => PreferredContactValueMessages::invalidFormatRu($preferred),
                ]);
            }
            $preferredValue = $v;
            $row = ['type' => ContactChannelType::Max->value, 'value' => $v];
            if ($extraRaw !== $v) {
                $row['raw_value'] = $extraRaw;
            }
            $channels[] = $row;
        }

        if ($preferredValue === null) {
            $preferredValue = $phone;
        }

        foreach ($channels as $i => $ch) {
            if (isset($ch['raw_value']) && $ch['raw_value'] === '') {
                unset($channels[$i]['raw_value']);
            }
        }

        return [
            'preferred_contact_channel' => $preferred,
            'preferred_contact_value' => $preferredValue,
            'visitor_contact_channels_json' => array_values($channels),
        ];
    }
}
