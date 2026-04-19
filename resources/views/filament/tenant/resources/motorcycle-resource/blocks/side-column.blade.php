@props(['recordId'])

@php
    $id = (int) $recordId;
@endphp

<div class="space-y-6">
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleLocationEditor::class, ['recordId' => $id], key('moto-block-loc-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePublishingEditor::class, ['recordId' => $id], key('moto-block-pub-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePricingProfileEditor::class, ['recordId' => $id], key('moto-block-pricing-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleMediaEditor::class, ['recordId' => $id], key('moto-block-media-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSeoEditor::class, ['recordId' => $id], key('moto-block-seo-'.$id))
</div>
