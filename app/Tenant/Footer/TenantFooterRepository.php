<?php

declare(strict_types=1);

namespace App\Tenant\Footer;

use App\Models\TenantFooterSection;
use Illuminate\Database\Eloquent\Collection;

final class TenantFooterRepository
{
    /**
     * @return Collection<int, TenantFooterSection>
     */
    public function enabledSectionsWithLinks(int $tenantId): Collection
    {
        return TenantFooterSection::query()
            ->where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'links' => static fn ($q) => $q
                    ->where('is_enabled', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->get();
    }
}
