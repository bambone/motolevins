@props(['recordId'])

@php
    $id = (int) $recordId;
@endphp

<div class="space-y-6">
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleLocationEditor::class, ['recordId' => $id], key('moto-block-loc-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePublishingEditor::class, ['recordId' => $id], key('moto-block-pub-'.$id))
</div>
