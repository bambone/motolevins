<?php

namespace App\Filament\Support;

use App\Models\TenantDomain;

/**
 * Человекочитаемые статусы домена и подсказки «что делать дальше».
 */
final class TenantDomainStatusCopy
{
    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            TenantDomain::STATUS_PENDING => 'Ожидает',
            TenantDomain::STATUS_VERIFYING => 'Проверяется',
            TenantDomain::STATUS_ACTIVE => 'Активен',
            TenantDomain::STATUS_FAILED => 'Ошибка',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sslOptions(): array
    {
        return [
            TenantDomain::SSL_NOT_REQUIRED => 'Не требуется (поддомен)',
            TenantDomain::SSL_PENDING => 'Ожидает выпуска',
            TenantDomain::SSL_ISSUED => 'Выпущен',
            TenantDomain::SSL_FAILED => 'Ошибка выпуска',
        ];
    }

    /**
     * @deprecated Legacy column verification_status; use statusOptions / statusLabel.
     *
     * @return array<string, string>
     */
    public static function verificationOptions(): array
    {
        return self::statusOptions();
    }

    public static function statusLabel(?string $state): string
    {
        if ($state === null || $state === '') {
            return '—';
        }

        return self::statusOptions()[$state] ?? $state;
    }

    public static function verificationLabel(?string $state): string
    {
        return self::statusLabel($state);
    }

    public static function sslLabel(?string $state): string
    {
        if ($state === null || $state === '') {
            return '—';
        }

        return self::sslOptions()[$state] ?? $state;
    }

    public static function statusNextStep(?string $state, ?string $dnsTarget, string $host): string
    {
        return match ($state) {
            TenantDomain::STATUS_PENDING, TenantDomain::STATUS_VERIFYING => $dnsTarget
                ? 'Добавьте у регистратора DNS-запись по инструкции платформы. Цель: '.$dnsTarget.'.'
                : 'Добавьте DNS-записи у регистратора домена согласно инструкции подключения домена.',
            TenantDomain::STATUS_FAILED => 'Проверьте DNS-записи и имя домена ('.$host.').',
            TenantDomain::STATUS_ACTIVE => 'Домен активен. При необходимости назначьте его основным.',
            default => 'При необходимости обратитесь в поддержку.',
        };
    }

    public static function verificationNextStep(?string $state, ?string $dnsTarget, string $host): string
    {
        return self::statusNextStep($state, $dnsTarget, $host);
    }

    public static function sslNextStep(?string $state): string
    {
        return match ($state) {
            TenantDomain::SSL_NOT_REQUIRED => 'Для поддомена платформы отдельный сертификат не выпускается.',
            TenantDomain::SSL_PENDING => 'После подтверждения домена сертификат выпускается автоматически (очередь).',
            TenantDomain::SSL_ISSUED => 'Сертификат действует. Сайт доступен по HTTPS.',
            TenantDomain::SSL_FAILED => 'Проверьте DNS и доступность домена с сервера.',
            default => 'Статус SSL обновится после корректной настройки DNS.',
        };
    }
}
