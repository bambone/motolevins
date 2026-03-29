<?php

namespace App\Product\Settings;

use App\Models\PlatformSetting;
use Illuminate\Support\Str;

/**
 * Чтение platform-level почтовых и брендовых настроек для продукта (не SMTP-транспорт).
 *
 * Централизует ключи email.* / platform_name; UI и Mailable не должны разбрасывать PlatformSetting::get().
 */
final class ProductMailSettingsResolver
{
    public function platformBrandName(): string
    {
        return (string) PlatformSetting::get(
            'platform_name',
            config('platform_marketing.brand_name', config('app.name'))
        );
    }

    public function defaultFromAddress(): string
    {
        return (string) PlatformSetting::get('email.default_from_address', config('mail.from.address', ''));
    }

    public function defaultFromName(): string
    {
        $brand = $this->platformBrandName();

        return (string) PlatformSetting::get('email.default_from_name', $brand);
    }

    /**
     * Получатели уведомлений по маркетинговой форме контактов (платформа).
     *
     * @return list<string>
     */
    public function resolvePlatformContactRecipients(): array
    {
        $raw = PlatformSetting::get('email.contact_form_recipients', '');
        if (is_string($raw) && trim($raw) !== '') {
            $parsed = $this->parseRecipientList($raw);
            if ($parsed !== []) {
                return $parsed;
            }
        }

        $envTo = trim((string) config('platform_marketing.contact_mail_to', ''));
        if ($envTo !== '') {
            return $this->parseRecipientList($envTo);
        }

        $from = trim((string) config('mail.from.address', ''));

        return $from !== '' ? [$from] : [];
    }

    /**
     * @return list<string>
     */
    private function parseRecipientList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        if (Str::startsWith($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', array_map('strval', $decoded))));
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
