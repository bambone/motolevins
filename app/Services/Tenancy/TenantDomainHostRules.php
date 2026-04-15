<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\TenantDomain;
use App\Tenant\HostClassifier;
use Illuminate\Validation\ValidationException;

/**
 * Единая валидация и канонизация значения host для {@see TenantDomain}.
 *
 * Политика: не «чинить» ввод с протоколом/путём — отклонять. Допустимы только trim,
 * lower-case, финальная точка, Unicode → punycode (IDNA UTS46).
 */
final class TenantDomainHostRules
{
    public const MESSAGE_INVALID_FORMAT = 'Введите корректный домен, например example.com. Укажите домен без протокола (http/https), без пути и без пробелов.';

    public function __construct(
        private HostClassifier $hostClassifier
    ) {}

    /**
     * Синтаксис и «локальная» политика имени (без привязки к клиенту).
     * Поддомен платформы может наследовать slug с «_»; пользовательские домены — нет.
     *
     * @throws ValidationException
     */
    public function assertValidHostFormat(string $raw, ?string $domainType = null): string
    {
        $allowUnderscoreInLabels = $domainType === TenantDomain::TYPE_SUBDOMAIN;

        return $this->parseCanonicalHostOrThrow($raw, $allowUnderscoreInLabels);
    }

    /**
     * Резервы платформы, зона поддоменов для custom, уникальность.
     *
     * @throws ValidationException
     */
    public function assertAttachableOrThrow(
        string $canonicalHost,
        int $tenantId,
        ?int $ignoreTenantDomainId,
        string $domainType,
    ): void {
        if ($this->hostClassifier->isNonTenantHost($canonicalHost)) {
            throw ValidationException::withMessages([
                'host' => 'Этот адрес зарезервирован платформой и не может использоваться как домен клиента.',
            ]);
        }

        $root = $this->normalizedRootDomain();
        if ($domainType === TenantDomain::TYPE_CUSTOM && $root !== '' && str_ends_with($canonicalHost, '.'.$root)) {
            throw ValidationException::withMessages([
                'host' => 'Поддомены платформы (*.'.$root.') выдаются автоматически; укажите свой домен.',
            ]);
        }

        $query = TenantDomain::query()->where('host', $canonicalHost);
        if ($ignoreTenantDomainId !== null) {
            $query->whereKeyNot($ignoreTenantDomainId);
        }

        $existing = $query->first();

        if ($existing !== null && (int) $existing->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'host' => 'Домен уже подключён к другому клиенту.',
            ]);
        }

        if ($existing !== null && (int) $existing->tenant_id === $tenantId) {
            throw ValidationException::withMessages([
                'host' => 'Этот домен уже добавлен для этого клиента.',
            ]);
        }
    }

    /**
     * Полная проверка перед записью: формат, резервы платформы, конфликт с корневой зоной для custom, уникальность.
     *
     * @throws ValidationException
     */
    public function validateAndCanonicalize(
        string $raw,
        int $tenantId,
        ?int $ignoreTenantDomainId,
        string $domainType,
    ): string {
        $canonical = $this->assertValidHostFormat($raw, $domainType);
        $this->assertAttachableOrThrow($canonical, $tenantId, $ignoreTenantDomainId, $domainType);

        return $canonical;
    }

    /**
     * Только синтаксис host (для аудита данных, без проверки уникальности и резервов).
     */
    public function tryCanonicalizeFormat(string $raw, ?string $domainType = null): ?string
    {
        try {
            return $this->assertValidHostFormat($raw, $domainType);
        } catch (ValidationException) {
            return null;
        }
    }

    /**
     * @return list<array{issue:string, detail:string}>
     */
    public function diagnoseStoredHostIssues(string $storedHost, ?string $storedType = null): array
    {
        $issues = [];

        $canonical = $this->tryCanonicalizeFormat($storedHost, $storedType);
        if ($canonical === null) {
            $issues[] = [
                'issue' => 'invalid_format',
                'detail' => self::MESSAGE_INVALID_FORMAT,
            ];

            return $issues;
        }

        if ($this->hostClassifier->isNonTenantHost($canonical)) {
            $issues[] = [
                'issue' => 'reserved_platform_or_central_host',
                'detail' => 'Хост совпадает с маркетинговым/платформенным доменом.',
            ];
        }

        return $issues;
    }

    /**
     * @throws ValidationException
     */
    private function parseCanonicalHostOrThrow(string $raw, bool $allowUnderscoreInLabels = false): string
    {
        if ($raw !== trim($raw)) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (preg_match('/\s/u', $trimmed) === 1) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (str_contains($trimmed, '://') || str_contains($trimmed, '/') || str_contains($trimmed, '?') || str_contains($trimmed, '#')) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (str_contains($trimmed, ':')) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        $host = mb_strtolower($trimmed, 'UTF-8');
        $host = rtrim($host, '.');

        if ($host === '' || str_starts_with($host, '.') || str_contains($host, '..')) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (str_contains($host, '*')) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (! $allowUnderscoreInLabels && str_contains($host, '_')) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (preg_match('/[^\x00-\x7F]/u', $host) === 1) {
            if (! function_exists('idn_to_ascii')) {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }

            $ascii = @idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }

            $host = $ascii;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if ($host === 'localhost') {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        $labels = explode('.', $host);
        if (count($labels) < 2) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        if (strlen($host) > 253) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        foreach ($labels as $label) {
            if ($label === '') {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }

            $len = strlen($label);
            if ($len > 63) {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }

            if (str_starts_with($label, '-') || str_ends_with($label, '-')) {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }

            $labelPattern = $allowUnderscoreInLabels ? '/^[a-z0-9_-]+$/' : '/^[a-z0-9-]+$/';

            if (preg_match($labelPattern, $label) !== 1) {
                throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
            }
        }

        $tld = $labels[count($labels) - 1];
        if (strlen($tld) < 2 || ctype_digit($tld)) {
            throw ValidationException::withMessages(['host' => self::MESSAGE_INVALID_FORMAT]);
        }

        return $host;
    }

    private function normalizedRootDomain(): string
    {
        return TenantDomain::normalizeHost((string) config('tenancy.root_domain', ''));
    }
}
