<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Page extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'template',
        'status',
        'published_at',
        'show_in_main_menu',
        'main_menu_sort_order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'show_in_main_menu' => 'boolean',
        'main_menu_sort_order' => 'integer',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Черновик',
            'published' => 'Опубликован',
            'hidden' => 'Скрыт',
        ];
    }
}
