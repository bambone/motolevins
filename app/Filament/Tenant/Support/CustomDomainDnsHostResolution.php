<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

/**
 * Параметры отображения DNS-инструкции для кастомного домена: apex vs поддомен, punycode vs отображение.
 *
 * Политика v1 (простая): «apex» = ровно две метки в имени ({@see self::isApexHost}).
 * Для многоуровневых зон вроде example.co.uk поведение может быть неточным — см. комментарий в коде.
 */
final class CustomDomainDnsHostResolution
{
    /**
     * @return array{
     *     dnsHost: string,
     *     displayHost: string,
     *     isApex: bool,
     *     showWwwCnameRow: bool,
     *     aRecordNameHint: string,
     *     subdomainDnsNote: string|null,
     * }
     */
    public static function resolve(string $canonicalHost): array
    {
        $dnsHost = $canonicalHost;
        $displayHost = self::toDisplayHost($canonicalHost);
        $isApex = self::isApexHost($canonicalHost);

        // Для apex показываем CNAME www → dnsHost. Для поддомена (в т.ч. www.example.com) отдельная строка www была бы некорректной.
        $showWwwCnameRow = $isApex;
        $aRecordNameHint = $isApex ? '@' : self::relativeHostNameForARecord($canonicalHost);

        $subdomainDnsNote = null;
        if (! $isApex) {
            $subdomainDnsNote = 'Вы подключаете поддомен, а не «голый» домен второго уровня. '
                .'Отдельная запись для имени www в этой инструкции обычно не нужна: она относится только к корневому домену вида example.com. '
                .'Для записи типа A укажите в панели регистратора имя хоста, соответствующее вашему поддомену (см. колонку «Имя» ниже). '
                .'Если сомневаетесь, уточните у поддержки регистратора.';
        }

        return [
            'dnsHost' => $dnsHost,
            'displayHost' => $displayHost,
            'isApex' => $isApex,
            'showWwwCnameRow' => $showWwwCnameRow,
            'aRecordNameHint' => $aRecordNameHint,
            'subdomainDnsNote' => $subdomainDnsNote,
        ];
    }

    /**
     * Относительное имя для A в зоне родительского домена: для www.example.com → «www», для a.b.example.com → «a.b».
     */
    private static function relativeHostNameForARecord(string $asciiHost): string
    {
        $host = trim($asciiHost, '.');
        $labels = explode('.', $host);
        if (count($labels) <= 2) {
            return '@';
        }

        return implode('.', array_slice($labels, 0, -2));
    }

    /**
     * Эвристика apex: ровно две метки (example.com). Не учитывает публичные суффиксы вида co.uk.
     */
    private static function isApexHost(string $host): bool
    {
        $host = trim($host, '.');

        return substr_count($host, '.') === 1;
    }

    private static function toDisplayHost(string $asciiHost): string
    {
        if (! function_exists('idn_to_utf8')) {
            return $asciiHost;
        }

        $unicode = @idn_to_utf8($asciiHost, IDNA_NONTRANSITIONAL_TO_UNICODE, INTL_IDNA_VARIANT_UTS46);

        return $unicode !== false ? $unicode : $asciiHost;
    }
}
