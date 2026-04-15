<?php

namespace App\Contracts;

/**
 * Livewire hosts that back teleported Filament forms and rely on full snapshot re-renders.
 */
interface ForcesFullLivewireRender
{
    public function forceFullLivewireRender(): void;
}
