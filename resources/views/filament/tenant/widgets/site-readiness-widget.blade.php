@php
    $summary = $this->summary;
    $centerUrl = $this->centerUrl;
    $progressWidth = $summary ? min(100, max(0, (int) ($summary['completion_percent'] ?? 0))) : 0;
@endphp

@if($summary)
    <x-filament::section>
        <x-slot name="heading">
            Готовность сайта
        </x-slot>
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $summary['completion_percent'] }}%
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Заполнено {{ $summary['completed_count'] }} из {{ $summary['applicable_count'] }}
                </div>
            </div>
            <svg
                class="h-2 w-full overflow-hidden rounded-full text-gray-200 dark:text-gray-700"
                viewBox="0 0 100 2"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <rect width="100" height="2" class="fill-current" />
                <rect width="{{ $progressWidth }}" height="2" class="fill-amber-500" />
            </svg>
            @if(($summary['launch_critical_remaining'] ?? 0) > 0)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    До минимального запуска осталось шагов: <span class="font-medium">{{ $summary['launch_critical_remaining'] }}</span>
                </p>
            @endif
            @if(! empty($summary['next_pending_items']))
                <ul class="list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
                    @foreach(array_slice($summary['next_pending_items'], 0, 3) as $item)
                        <li>{{ $item['title'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="flex flex-wrap gap-2">
                <x-filament::button tag="a" href="{{ $centerUrl }}" size="sm" color="primary">
                    Центр настройки
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
@endif
