<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class TenantLocation extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_locations';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'city',
        'region',
        'country',
        'address',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (TenantLocation $location) {
            if (empty($location->slug) && ! empty($location->name)) {
                $base = Str::slug($location->name) ?: 'location';
                $slug = $base;
                $i = 1;
                $tenantId = $location->tenant_id;
                $q = static::withoutGlobalScopes()
                    ->where('slug', $slug)
                    ->where('tenant_id', $tenantId);
                while ($q->exists()) {
                    $slug = $base.'-'.$i++;
                    $q = static::withoutGlobalScopes()
                        ->where('slug', $slug)
                        ->where('tenant_id', $tenantId);
                }
                $location->slug = $slug;
            }
        });
    }

    public function motorcycles(): BelongsToMany
    {
        return $this->belongsToMany(Motorcycle::class, 'motorcycle_tenant_location')
            ->withTimestamps();
    }

    public function rentalUnits(): BelongsToMany
    {
        return $this->belongsToMany(RentalUnit::class, 'rental_unit_tenant_location')
            ->withTimestamps();
    }
}
