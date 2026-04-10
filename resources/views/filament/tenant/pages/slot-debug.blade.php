@php
    /** @var \App\Filament\Tenant\Pages\SlotDebugPage $this */
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Параметры</x-slot>
            <x-slot name="description">Те же правила, что и у публичного API слотов: weekly rules, исключения, busy, сервисные буферы.</x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="slot-debug-service">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Услуга</span>
                    </label>
                    <select
                        id="slot-debug-service"
                        name="bookable_service_id"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        wire:model.live="bookable_service_id"
                    >
                        <option value="">—</option>
                        @foreach (\App\Models\BookableService::query()->where('scheduling_scope', \App\Scheduling\Enums\SchedulingScope::Tenant)->where('tenant_id', currentTenant()?->id)->orderBy('title')->get() as $svc)
                            <option value="{{ $svc->id }}">{{ $svc->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="slot-debug-from">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">С даты (UTC)</span>
                    </label>
                    <input
                        id="slot-debug-from"
                        type="date"
                        name="range_from"
                        wire:model.live="range_from"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                </div>
                <div class="space-y-1">
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="slot-debug-to">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">По дату (UTC)</span>
                    </label>
                    <input
                        id="slot-debug-to"
                        type="date"
                        name="range_to"
                        wire:model.live="range_to"
                        class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    />
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Результат</x-slot>
            @php($rows = $this->debugSlots)
            @if ($rows === [])
                <p class="text-sm text-gray-600 dark:text-gray-400">Выберите услугу или проверьте, что у target включена запись и есть ресурсы с правилами.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4 font-medium">Начало (UTC)</th>
                                <th class="py-2 pr-4 font-medium">Конец</th>
                                <th class="py-2 pr-4 font-medium">Ресурс</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $row['starts_at_utc'] ?? '' }}</td>
                                    <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $row['ends_at_utc'] ?? '' }}</td>
                                    <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $row['scheduling_resource_label'] ?? '' }} (#{{ $row['scheduling_resource_id'] ?? '' }})</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
