<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Публичные отзывы: HTML-страница и JSON для будущего SPA/блока на главной.
 */
final class TenantPublicReviewsController extends Controller
{
    public function show(): View
    {
        abort_if(tenant() === null, 404);

        $reviews = $this->publishedReviewsQuery()->get();

        return tenant_view('pages.reviews', [
            'reviews' => $reviews,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        abort_if(tenant() === null, 404);

        $limit = min(100, max(1, (int) $request->query('limit', 50)));

        $reviews = $this->publishedReviewsQuery()
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $reviews->map(fn (Review $r): array => $this->reviewToApiArray($r))->values()->all(),
        ]);
    }

    private function publishedReviewsQuery()
    {
        return Review::query()
            ->where('status', 'published')
            ->with(['motorcycle:id,name'])
            ->orderBy('sort_order')
            ->orderByDesc('is_featured')
            ->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewToApiArray(Review $r): array
    {
        $canonical = $r->publicFullTextRaw();

        return [
            'id' => $r->id,
            'name' => $r->name,
            'city' => $r->city,
            'text' => $canonical,
            'body' => $canonical,
            'rating' => $r->rating,
            'date' => $r->date?->toDateString(),
            'source' => $r->source,
            'is_featured' => (bool) $r->is_featured,
            'sort_order' => $r->sort_order,
            'avatar_url' => $r->avatar_url,
            'motorcycle' => $r->motorcycle !== null
                ? ['id' => $r->motorcycle->id, 'name' => $r->motorcycle->name]
                : null,
        ];
    }
}
