@props(['recordId'])

@php
    $id = (int) $recordId;
@endphp

<div class="fi-motorcycle-edit-below-fold space-y-8 pt-2">
    <div class="space-y-6">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePricingProfileEditor::class, ['recordId' => $id], key('moto-block-pricing-'.$id))
    </div>

    <div class="space-y-6">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSpecsEditor::class, ['recordId' => $id], key('moto-block-specs-'.$id))
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleDescriptionEditor::class, ['recordId' => $id], key('moto-block-desc-'.$id))
    </div>

    <div class="space-y-6">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleMediaEditor::class, ['recordId' => $id], key('moto-block-media-'.$id))
    </div>

    <div class="space-y-6">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSeoEditor::class, ['recordId' => $id], key('moto-block-seo-'.$id))
    </div>
</div>
