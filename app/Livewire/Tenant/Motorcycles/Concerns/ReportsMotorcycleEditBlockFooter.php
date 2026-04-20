<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Motorcycles\Concerns;

use Illuminate\Support\Carbon;

trait ReportsMotorcycleEditBlockFooter
{
    public ?string $motorcycleEditSavedAt = null;

    protected function touchMotorcycleEditSavedTimestamp(): void
    {
        $this->motorcycleEditSavedAt = now()->toIso8601String();
    }

    protected function motorcycleEditFooterStatus(bool $dirty): string
    {
        if ($dirty) {
            return 'Есть несохранённые изменения';
        }

        if ($this->motorcycleEditSavedAt !== null) {
            return 'Сохранено · '.Carbon::parse($this->motorcycleEditSavedAt)->diffForHumans(short: true, parts: 1);
        }

        return 'Сохранено';
    }
}
