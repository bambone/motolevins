@php
    /** @var \App\Filament\Tenant\Pages\CalendarSyncHealthPage $this */
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Подключения</x-slot>
        <x-slot name="description">Последний успешный sync, ошибки и устаревание busy — см. также карточку подключения и действие «Синхр. busy».</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4 font-medium">Название</th>
                        <th class="py-2 pr-4 font-medium">Провайдер</th>
                        <th class="py-2 pr-4 font-medium">Активно</th>
                        <th class="py-2 pr-4 font-medium">Последний успешный sync</th>
                        <th class="py-2 pr-4 font-medium">Ошибка</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($this->connections as $c)
                        <tr>
                            <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $c->display_name ?? '—' }}</td>
                            <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $c->provider?->value ?? $c->provider }}</td>
                            <td class="py-2 pr-4">{{ $c->is_active ? 'да' : 'нет' }}</td>
                            <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $c->last_successful_sync_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit((string) ($c->last_error ?? ''), 80) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-gray-600 dark:text-gray-400">Нет подключений. Создайте запись в разделе «Календари (подключения)».</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
