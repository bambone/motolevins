<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'question',
        'answer',
        'category',
        'sort_order',
        'status',
        'show_on_home',
    ];

    protected $casts = [
        'show_on_home' => 'boolean',
    ];

    public static function statuses(): array
    {
        return [
            'draft' => 'Черновик',
            'published' => 'Опубликован',
            'hidden' => 'Скрыт',
        ];
    }

    /**
     * Общий FAQ: главная (блок) и публичная страница /faq. Вопросы по одной услуге (категория = slug) остаются с show_on_home = false.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForPublicHubAndFaqPage(Builder $query): Builder
    {
        return $query->where('show_on_home', true);
    }
}
