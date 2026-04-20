@props(['recordId'])

@php
    $id = (int) $recordId;
@endphp

<div class="fi-motorcycle-primary-flow space-y-6">
    <div id="moto-main" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleMainInfoEditor::class, ['recordId' => $id], key('moto-block-main-'.$id))
    </div>

    <div id="moto-pricing" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePricingProfileEditor::class, ['recordId' => $id], key('moto-block-pricing-'.$id))
    </div>

    <div id="moto-media" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleMediaEditor::class, ['recordId' => $id], key('moto-block-media-'.$id))
    </div>

    <div id="moto-page" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePageModelEditor::class, ['recordId' => $id], key('moto-block-page-'.$id))
    </div>

    <div id="moto-specs" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSpecsEditor::class, ['recordId' => $id], key('moto-block-specs-'.$id))
    </div>

    <div id="moto-desc" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleDescriptionEditor::class, ['recordId' => $id], key('moto-block-desc-'.$id))
    </div>

    <div id="moto-seo" class="fi-moto-section-anchor">
        @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSeoEditor::class, ['recordId' => $id], key('moto-block-seo-'.$id))
    </div>
</div>
