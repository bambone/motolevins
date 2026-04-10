@props(['recordId'])

@php
    use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
    use App\Filament\Tenant\Resources\MotorcycleResource;

    $id = (int) $recordId;
    $summaryMotorcycle = MotorcycleResource::getEloquentQuery()->whereKey($id)->first();
@endphp

<div class="space-y-6">
    @if ($summaryMotorcycle && LinkedBookableSchedulingForm::schedulingUiVisible())
        <div class="fi-section rounded-xl border border-gray-200 p-4 shadow-sm dark:border-white/10">
            {!! LinkedBookableSchedulingForm::motorcycleEditSummaryHtml($summaryMotorcycle) !!}
        </div>
    @endif
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleMainInfoEditor::class, ['recordId' => $id], key('moto-block-main-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePageModelEditor::class, ['recordId' => $id], key('moto-block-page-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSpecsEditor::class, ['recordId' => $id], key('moto-block-specs-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleDescriptionEditor::class, ['recordId' => $id], key('moto-block-desc-'.$id))
</div>
