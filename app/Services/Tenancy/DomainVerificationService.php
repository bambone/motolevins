<?php

namespace App\Services\Tenancy;

use App\Models\TenantDomain;

class DomainVerificationService
{
    public function verificationHostname(TenantDomain $domain): string
    {
        $prefix = (string) config('tenancy.custom_domains.verification_prefix', '_rentbase-verification');

        return $prefix.'.'.$domain->host;
    }

    public function hasValidTxtToken(TenantDomain $domain): bool
    {
        $token = $domain->verification_token;
        if ($token === null || $token === '') {
            return false;
        }

        $host = $this->verificationHostname($domain);
        $records = @dns_get_record($host, DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $value = $record['txt'] ?? null;
            if ($value !== null && trim((string) $value) === trim((string) $token)) {
                return true;
            }
        }

        return false;
    }

    public function pointsToOurServer(TenantDomain $domain): bool
    {
        return $this->hostPointsToOurServer($domain->host);
    }

    public function hostPointsToOurServer(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);
        if ($host === '') {
            return false;
        }

        $expectedIp = (string) config('tenancy.server_ip', '');
        $cnameTarget = TenantDomain::normalizeHost((string) config('tenancy.cname_target', ''));

        $current = $host;
        $maxDepth = 5;

        for ($i = 0; $i < $maxDepth; $i++) {
            if ($expectedIp !== '' && $this->hostHasMatchingA($current, $expectedIp)) {
                return true;
            }

            if ($cnameTarget !== '' && $this->hostHasCnameTo($current, $cnameTarget)) {
                return true;
            }

            $next = $this->firstCnameTarget($current);
            if ($next === null || $next === $current) {
                return false;
            }

            $current = $next;
        }

        return false;
    }

    /**
     * True when TXT and routing checks pass (no DB writes).
     */
    public function dnsFullyVerified(TenantDomain $domain): bool
    {
        return $this->hasValidTxtToken($domain) && $this->pointsToOurServer($domain);
    }

    protected function hostHasMatchingA(string $host, string $expectedIp): bool
    {
        $records = @dns_get_record($host, DNS_A);
        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            if (($record['ip'] ?? null) === $expectedIp) {
                return true;
            }
        }

        return false;
    }

    protected function hostHasCnameTo(string $host, string $target): bool
    {
        $records = @dns_get_record($host, DNS_CNAME);
        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $t = TenantDomain::normalizeHost((string) ($record['target'] ?? ''));
            if ($t === $target) {
                return true;
            }
        }

        return false;
    }

    protected function firstCnameTarget(string $host): ?string
    {
        $records = @dns_get_record($host, DNS_CNAME);
        if (! is_array($records) || $records === []) {
            return null;
        }

        $t = $records[0]['target'] ?? null;
        if ($t === null || $t === '') {
            return null;
        }

        return TenantDomain::normalizeHost((string) $t);
    }
}
