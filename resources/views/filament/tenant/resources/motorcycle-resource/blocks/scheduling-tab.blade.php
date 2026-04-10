@props(['recordId'])

@livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleSchedulingEditor::class, ['recordId' => (int) $recordId], key('moto-block-sched-'.(int) $recordId))
