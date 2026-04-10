<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles\Concerns;

use App\Filament\Tenant\Resources\MotorcycleResource;
use App\Models\Motorcycle;
use Illuminate\Support\Facades\Gate;

trait ResolvesMotorcycleRecord
{
    public int $recordId;

    protected function resolveMotorcycle(): Motorcycle
    {
        /** @var Motorcycle $motorcycle */
        $motorcycle = MotorcycleResource::getEloquentQuery()
            ->whereKey($this->recordId)
            ->firstOrFail();

        Gate::authorize('update', $motorcycle);

        return $motorcycle;
    }
}
