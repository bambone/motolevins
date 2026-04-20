<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

use App\ContactChannels\TenantPublicSiteContactsService;
use App\Models\Tenant;
use App\Models\TenantSetting;

/**
 * Единый DTO контактов для подвала: готовые URL и подписи, без сборки ссылок в Blade.
 */
final class TenantFooterContactPresentation
{
    /**
     * @return array{
     *   phone_display: string,
     *   phone_href: string,
     *   telegram_handle: string,
     *   telegram_display: string,
     *   telegram_url: string,
     *   whatsapp_digits: string,
     *   whatsapp_url: string,
     *   email: string,
     *   email_href: string,
     *   vk_url: string,
     *   office_address: string
     * }
     */
    public static function forTenant(Tenant $tenant, TenantPublicSiteContactsService $contactsService): array
    {
        $c = $contactsService->contactsForPublicLayout($tenant);
        $tid = (int) $tenant->id;
        $office = trim((string) TenantSetting::getForTenant($tid, 'contacts.public_office_address', ''));
        if ($office === '') {
            $office = trim((string) TenantSetting::getForTenant($tid, 'contacts.address', ''));
        }

        $phoneDigits = preg_replace('/\D+/', '', $c['phone']) ?? '';
        $telegramHandle = $c['telegram'];
        $telegramUrl = $telegramHandle !== '' ? 'https://t.me/'.ltrim($telegramHandle, '@') : '';
        $whatsappDigits = $c['whatsapp'];
        $whatsappUrl = $whatsappDigits !== '' ? 'https://wa.me/'.$whatsappDigits : '';
        $email = $c['email'];
        $vkUrl = $c['vk_url'] ?? '';

        return [
            'phone_display' => $c['phone'],
            'phone_href' => $phoneDigits !== '' ? 'tel:'.$phoneDigits : '',
            'telegram_handle' => $telegramHandle,
            'telegram_display' => $telegramHandle !== '' ? '@'.$telegramHandle : '',
            'telegram_url' => $telegramUrl,
            'whatsapp_digits' => $whatsappDigits,
            'whatsapp_url' => $whatsappUrl,
            'email' => $email,
            'email_href' => $email !== '' ? 'mailto:'.$email : '',
            'vk_url' => $vkUrl,
            'office_address' => $office,
        ];
    }
}
