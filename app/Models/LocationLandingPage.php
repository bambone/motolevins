<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class LocationLandingPage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'h1',
        'intro',
        'body',
        'faq_json',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'faq_json' => 'array',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
