<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PlatformProductChangelogEntry extends Model
{
    protected $table = 'platform_product_changelog_entries';

    protected $fillable = [
        'entry_date',
        'title',
        'summary',
        'body',
        'sort_weight',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'sort_weight' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrderedForPublic(Builder $query): Builder
    {
        return $query
            ->orderByDesc('entry_date')
            ->orderByDesc('sort_weight')
            ->orderByDesc('id');
    }

    /**
     * @return Collection<string, Collection<int, PlatformProductChangelogEntry>>
     */
    public static function groupedPublishedForDisplay(): Collection
    {
        return static::query()
            ->published()
            ->orderedForPublic()
            ->get()
            ->groupBy(fn (self $entry): string => $entry->entry_date->format('Y-m-d'))
            ->sortKeysDesc()
            ->map(function (Collection $entries): Collection {
                return $entries->sort(function (self $a, self $b): int {
                    return [$b->sort_weight, $b->id] <=> [$a->sort_weight, $a->id];
                })->values();
            });
    }
}
