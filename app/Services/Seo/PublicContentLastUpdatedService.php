<?php

namespace App\Services\Seo;

use App\Models\Faq;
use App\Models\Motorcycle;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Review;
use App\Models\Tenant;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class PublicContentLastUpdatedService
{
    public function lastUpdatedAt(Tenant $tenant): ?CarbonInterface
    {
        $tenantId = $tenant->id;
        $timestamps = new Collection;

        $pageMax = Page::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->max('updated_at');
        if ($pageMax !== null) {
            $timestamps->push($pageMax);
        }

        $sectionMax = PageSection::query()
            ->where('tenant_id', $tenantId)
            ->max('updated_at');
        if ($sectionMax !== null) {
            $timestamps->push($sectionMax);
        }

        $motoMax = Motorcycle::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->max('updated_at');
        if ($motoMax !== null) {
            $timestamps->push($motoMax);
        }

        $faqMax = Faq::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->max('updated_at');
        if ($faqMax !== null) {
            $timestamps->push($faqMax);
        }

        $reviewMax = Review::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->max('updated_at');
        if ($reviewMax !== null) {
            $timestamps->push($reviewMax);
        }

        if ($timestamps->isEmpty()) {
            return null;
        }

        return $timestamps->map(fn ($t) => Carbon::parse($t))->max();
    }
}
