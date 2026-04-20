<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

enum TenantFooterLinkKind: string
{
    case Internal = 'internal';
    case External = 'external';
    case Phone = 'phone';
    case Email = 'email';
    case Telegram = 'telegram';
    case Whatsapp = 'whatsapp';
    case Document = 'document';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
