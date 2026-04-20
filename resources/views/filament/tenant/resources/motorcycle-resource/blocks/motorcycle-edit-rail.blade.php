@props(['recordId'])

@php
    use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
    use App\Filament\Tenant\Resources\MotorcycleResource;
    use App\Support\Motorcycle\MotorcycleEditCompleteness;

    $id = (int) $recordId;
    $railMotorcycle = MotorcycleResource::getEloquentQuery()->whereKey($id)->first();
    $checklist = $railMotorcycle ? MotorcycleEditCompleteness::checklistItems($railMotorcycle) : [];
    $previewUrl = $railMotorcycle && trim((string) $railMotorcycle->slug) !== ''
        ? route('motorcycle.show', ['slug' => $railMotorcycle->slug])
        : null;

    $statusLabel = function (string $s): string {
        return match ($s) {
            MotorcycleEditCompleteness::STATUS_OK => 'Готово',
            MotorcycleEditCompleteness::STATUS_WARN => 'Замечание',
            default => 'Нужно',
        };
    };
@endphp

<div class="fi-motorcycle-edit-rail-inner space-y-4">
    @if ($railMotorcycle)
        <div class="fi-section rounded-xl border border-gray-200 p-4 shadow-sm dark:border-white/10">
            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Сводка</h2>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                Статус и готовность карточки; формы редактирования — слева.
            </p>
            @if ($previewUrl)
                <div class="mt-3">
                    <a
                        href="{{ $previewUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="fi-btn fi-btn-color-gray fi-btn-outlined inline-flex w-full min-h-9 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" />
                        Просмотр на сайте
                    </a>
                </div>
            @endif

            <div class="mt-4 border-t border-gray-100 pt-3 dark:border-white/10">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Чеклист
                </h3>
                <ul class="mt-2 space-y-2">
                    @foreach ($checklist as $item)
                        <li class="flex gap-2 text-xs text-gray-800 dark:text-gray-200">
                            <span
                                class="mt-0.5 inline-block h-2 w-2 shrink-0 rounded-full @if ($item['status'] === MotorcycleEditCompleteness::STATUS_OK) bg-emerald-500 @elseif ($item['status'] === MotorcycleEditCompleteness::STATUS_WARN) bg-amber-500 @else bg-gray-300 dark:bg-gray-600 @endif"
                                aria-hidden="true"
                            ></span>
                            <span class="min-w-0 flex-1">
                                <span class="font-medium">{{ $item['label'] }}</span>
                                <span class="sr-only">{{ $statusLabel($item['status']) }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($railMotorcycle && LinkedBookableSchedulingForm::schedulingUiVisible())
        <div class="fi-section rounded-xl border border-gray-200 p-4 shadow-sm dark:border-white/10">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Запись
            </h3>
            <div class="mt-2">
                {!! LinkedBookableSchedulingForm::motorcycleEditSummaryHtml($railMotorcycle) !!}
            </div>
        </div>
    @endif

    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcycleLocationEditor::class, ['recordId' => $id], key('moto-block-loc-'.$id))
    @livewire(\App\Livewire\Tenant\Motorcycles\MotorcyclePublishingEditor::class, ['recordId' => $id], key('moto-block-pub-'.$id))
</div>
