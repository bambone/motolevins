<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingResourceTypeLabel extends Model
{
    protected $table = 'scheduling_resource_type_labels';

    protected $fillable = [
        'tenant_id',
        'slug',
        'label',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
