<?php

namespace App\Support;

use App\ContactChannels\TenantPublicSiteContactsService;
use App\Models\Tenant;

/**
 * URL вторичного CTA (WhatsApp) для `resources/views/tenant/components/final-cta.blade.php`:
 * prop компонента / data_json.whatsapp_url, иначе {@see TenantPublicSiteContactsService} (без $contacts в шаблоне).
 */
final class FinalCtaWhatsappUrl
{
    /**
     * @param  string|null  $whatsAppUrlProp  prop Blade: полная ссылка; null — взять из секции/сервиса; **пустая строка** — выключить WA без fallback
     * @param  string  $whatsAppUrlFromSection  data_json.whatsapp_url (полный URL или цифры)
     * @param  bool  $showSecondary
     */
    public static function resolve(
        ?string $whatsAppUrlProp,
        string $whatsAppUrlFromSection,
        bool $showSecondary,
        ?Tenant $tenant,
        TenantPublicSiteContactsService $contacts,
    ): string {
        if (! $showSecondary) {
            return '';
        }
        if ($whatsAppUrlProp !== null) {
            if (trim($whatsAppUrlProp) === '') {
                return '';
            }

            return trim($whatsAppUrlProp);
        }
        if (trim($whatsAppUrlFromSection) !== '') {
            $u = trim($whatsAppUrlFromSection);
            if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
                return $u;
            }
            $d = preg_replace('/\D+/', '', $u) ?? '';

            return $d !== '' ? 'https://wa.me/'.$d : '';
        }
        if ($tenant === null) {
            return '';
        }
        $digits = (string) ($contacts->contactsForPublicLayout($tenant)['whatsapp'] ?? '');

        return $digits !== '' ? 'https://wa.me/'.$digits : '';
    }
}
