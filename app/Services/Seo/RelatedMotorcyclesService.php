<?php

namespace App\Services\Seo;

use App\Models\Motorcycle;
use Illuminate\Support\Collection;

/**
 * Tenant-scoped related motorcycles for public detail pages (not Blade queries).
 */
final class RelatedMotorcyclesService
{
    /**
     * @return Collection<int, Motorcycle>
     */
    public function forMotorcycle(Motorcycle $motorcycle, int $limit = 3): Collection
    {
        $limit = max(1, $limit);

        $relatedQuery = Motorcycle::query()
            ->where('tenant_id', $motorcycle->tenant_id)
            ->where('show_in_catalog', true)
            ->where('status', 'available')
            ->where('id', '!=', $motorcycle->id)
            ->with(['category', 'media'])
            ->orderBy('sort_order');

        $related = (clone $relatedQuery)
            ->when($motorcycle->category_id, fn ($q) => $q->where('category_id', $motorcycle->category_id))
            ->limit($limit)
            ->get();

        if ($related->count() < $limit) {
            $more = $relatedQuery
                ->whereNotIn('id', $related->pluck('id'))
                ->limit($limit - $related->count())
                ->get();
            $related = $related->concat($more);
        }

        return $related;
    }
}
