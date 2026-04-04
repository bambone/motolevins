<?php

namespace App\PageBuilder\Contacts;

enum ContactChannelType: string
{
    case Phone = 'phone';
    case Email = 'email';
    case Telegram = 'telegram';
    case Vk = 'vk';
    case SiteForm = 'site_form';
    case Whatsapp = 'whatsapp';
    case Viber = 'viber';
    case Instagram = 'instagram';
    case FacebookMessenger = 'facebook_messenger';
    case Sms = 'sms';
    case Max = 'max';
    case GenericUrl = 'generic_url';

    public static function tryFromMixed(mixed $raw): ?self
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return self::tryFrom($raw);
    }
}
