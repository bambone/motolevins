@props(['recordId'])

@php
    $id = (int) $recordId;
@endphp

<div class="space-y-4">
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleRentalUnitsPanel::class, ['motorcycleId' => $id], key('moto-fleet-panel-'.$id))
</div>
