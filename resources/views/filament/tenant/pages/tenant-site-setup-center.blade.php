<x-filament-panels::page>
    @php
        $summary = $this->summary;
    @endphp

    <div class="space-y-6">
        @if($this->hasPausedSession)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-50">
                Мастер на <strong>паузе</strong>. Нажмите «Продолжить мастер» выше или начните «Новая очередь», если хотите пересобрать шаги с нуля.
            </div>
        @endif

        <p class="text-sm text-gray-600 dark:text-gray-400">
            <a
                href="{{ \App\Filament\Tenant\Pages\TenantSiteSetupProfilePage::getUrl() }}"
                wire:navigate
                class="font-medium text-primary-600 underline decoration-primary-600/30 hover:decoration-primary-600 dark:text-primary-400"
            >
                Анкета настройки
            </a>
            — кратко о целях сайта (необязательно, помогает приоритизировать шаги).
        </p>

        <x-filament::section>
            <x-slot name="heading">Прогресс</x-slot>
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-3xl font-semibold">{{ $summary['completion_percent'] ?? 0 }}%</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $summary['completed_count'] ?? 0 }} из {{ $summary['applicable_count'] ?? 0 }} применимых пунктов
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Критично для запуска: осталось {{ $summary['launch_critical_remaining'] ?? 0 }} из {{ $summary['launch_critical_total'] ?? 0 }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Пункты</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">Категория</th>
                            <th class="py-2 pr-4">Пункт</th>
                            <th class="py-2 pr-4">Статус</th>
                            <th class="py-2">Сейчас</th>
                            <th class="py-2 pr-2 text-right">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->categoryRows as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $row['category'] }}</td>
                                <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $row['title'] }}</td>
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $row['execution_label'] ?? '' }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['snapshot'] }}</td>
                                <td class="py-2 pr-2 text-right align-top">
                                    @if(!empty($row['can_restore']))
                                        <form method="post" action="{{ route('filament.admin.tenant-site-setup.items.restore') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="item_key" value="{{ $row['key'] }}" />
                                            <button
                                                type="submit"
                                                class="text-xs font-semibold text-primary-600 underline hover:text-primary-500 dark:text-primary-400"
                                            >
                                                Вернуть в работу
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
