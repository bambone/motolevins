@props(['recordId'])

@php
    $id = (int) $recordId;
    $record = \App\Models\Motorcycle::query()->find($id);
    $tocSections = $record instanceof \App\Models\Motorcycle
        ? \App\Support\Motorcycle\MotorcycleEditCompleteness::tocSections($record)
        : [];
@endphp

<div class="fi-motorcycle-edit-canvas w-full min-w-0 space-y-6 lg:space-y-8">
    <div class="mx-auto w-full max-w-[min(100%,88rem)]">
        <div class="fi-motorcycle-edit-main-grid grid w-full min-w-0 grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
            <div class="fi-motorcycle-edit-primary-stack order-2 min-w-0 space-y-6 lg:order-1 lg:col-span-8">
                @if ($tocSections !== [])
                    <div class="fi-motorcycle-edit-toc-sticky -mx-4 px-4 lg:mx-0 lg:px-0">
                        @include('filament.tenant.resources.motorcycle-resource.blocks.motorcycle-edit-toc', [
                            'sections' => $tocSections,
                        ])
                    </div>
                @endif
                @include('filament.tenant.resources.motorcycle-resource.blocks.main-column', ['recordId' => $id])
            </div>
            <aside
                class="fi-motorcycle-edit-rail order-1 min-w-0 space-y-4 lg:order-2 lg:col-span-4 lg:space-y-0"
                aria-label="Сводка и публикация"
            >
                <div class="fi-motorcycle-rail-sticky-inner lg:sticky lg:top-[4.5rem] lg:max-h-[calc(100vh-5.5rem)] lg:overflow-y-auto lg:pb-2">
                    @include('filament.tenant.resources.motorcycle-resource.blocks.motorcycle-edit-rail', ['recordId' => $id])
                </div>
            </aside>
        </div>
    </div>
</div>
