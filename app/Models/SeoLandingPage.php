<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SeoLandingPage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'slug',
        'title',
        'h1',
        'intro',
        'body',
        'criteria_json',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'criteria_json' => 'array',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
