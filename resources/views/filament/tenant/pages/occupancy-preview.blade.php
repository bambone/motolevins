@php
    /** @var \App\Filament\Tenant\Pages\OccupancyPreviewPage $this */
    $tenant = currentTenant();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Параметры</x-slot>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="occ-res">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Ресурс</span>
                    </label>
                    <select
                        id="occ-res"
                        wire:model.live="scheduling_resource_id"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white"
                    >
                        <option value="">—</option>
                        @foreach (\App\Models\SchedulingResource::query()->where('scheduling_scope', \App\Scheduling\Enums\SchedulingScope::Tenant)->where('tenant_id', $tenant?->id)->orderBy('label')->get() as $r)
                            <option value="{{ $r->id }}">{{ $r->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="occ-tgt">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Target (для internal)</span>
                    </label>
                    <select
                        id="occ-tgt"
                        wire:model.live="scheduling_target_id"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white"
                    >
                        <option value="">— (только external)</option>
                        @foreach (\App\Models\SchedulingTarget::query()->where('scheduling_scope', \App\Scheduling\Enums\SchedulingScope::Tenant)->where('tenant_id', $tenant?->id)->orderBy('label')->get() as $t)
                            <option value="{{ $t->id }}">{{ $t->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label" for="occ-from"><span class="text-sm font-medium text-gray-950 dark:text-white">С (UTC)</span></label>
                    <input id="occ-from" type="date" wire:model.live="range_from" class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" />
                </div>
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label" for="occ-to"><span class="text-sm font-medium text-gray-950 dark:text-white">По (UTC)</span></label>
                    <input id="occ-to" type="date" wire:model.live="range_to" class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white" />
                </div>
            </div>
        </x-filament::section>

        @php($p = $this->previewPayload)
        <x-filament::section>
            <x-slot name="heading">Внутренние интервалы</x-slot>
            @if ($p['internal'] === [])
                <p class="text-sm text-gray-600 dark:text-gray-400">Выберите target или проверьте holds / manual blocks.</p>
            @else
                <ul class="list-inside list-disc text-sm text-gray-950 dark:text-white">
                    @foreach ($p['internal'] as $row)
                        <li>{{ $row['start'] }} — {{ $row['end'] }}</li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Внешний busy (кэш)</x-slot>
            @if ($p['external'] === [])
                <p class="text-sm text-gray-600 dark:text-gray-400">Нет записей в external_busy_blocks за период.</p>
            @else
                <ul class="list-inside list-disc text-sm text-gray-950 dark:text-white">
                    @foreach ($p['external'] as $row)
                        <li>{{ $row['start'] }} — {{ $row['end'] }} @if (! empty($row['is_tentative']))<span class="text-amber-600">(tentative)</span>@endif</li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
