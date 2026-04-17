@php
    $summary = $this->summary;
    $primaryCta = $this->primaryCta;
    $progressWidth = $summary ? min(100, max(0, (int) ($summary['completion_percent'] ?? 0))) : 0;
    $qPct = $summary ? min(100, max(0, (int) ($summary['quick_launch_percent'] ?? 0))) : 0;
    $ePct = $summary ? min(100, max(0, (int) ($summary['extended_percent'] ?? 0))) : 0;
    $qA = (int) ($summary['quick_launch_applicable'] ?? 0);
    $eA = (int) ($summary['extended_applicable'] ?? 0);
    $qC = (int) ($summary['quick_launch_completed'] ?? 0);
    $eC = (int) ($summary['extended_completed'] ?? 0);
@endphp

@if($summary && $primaryCta)
    <x-filament::section>
        <x-slot name="heading">
            Запуск сайта
        </x-slot>
        <x-slot name="description">
            Чеклист мастера — не весь кабинет; состав пунктов можно наращивать.
        </x-slot>
        <div class="space-y-3">
            @if($qA > 0 || $eA > 0)
                <div class="grid gap-3 sm:grid-cols-2">
                    @if($qA > 0)
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Быстрый запуск</p>
                            <p class="text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $qC }} из {{ $qA }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $qPct }}%)</span>
                            </p>
                            <svg
                                class="mt-1 h-2 w-full overflow-hidden rounded-full text-gray-200 dark:text-gray-700"
                                viewBox="0 0 100 2"
                                preserveAspectRatio="none"
                                aria-hidden="true"
                            >
                                <rect width="100" height="2" class="fill-current" />
                                <rect width="{{ $qPct }}" height="2" class="fill-primary-500" />
                            </svg>
                        </div>
                    @endif
                    @if($eA > 0)
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Расширенный запуск</p>
                            <p class="text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $eC }} из {{ $eA }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $ePct }}%)</span>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Пока небольшой набор — дальше подключается в реестре.</p>
                            <svg
                                class="mt-1 h-2 w-full overflow-hidden rounded-full text-gray-200 dark:text-gray-700"
                                viewBox="0 0 100 2"
                                preserveAspectRatio="none"
                                aria-hidden="true"
                            >
                                <rect width="100" height="2" class="fill-current" />
                                <rect width="{{ $ePct }}" height="2" class="fill-slate-500 dark:fill-slate-400" />
                            </svg>
                        </div>
                    @endif
                </div>
            @endif
            <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                <div class="flex flex-wrap items-baseline gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Сводка по чеклисту</span>
                    <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $summary['completion_percent'] }}%</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        ({{ $summary['completed_count'] }} из {{ $summary['applicable_count'] }})
                    </span>
                </div>
                <svg
                    class="mt-1 h-2 w-full overflow-hidden rounded-full text-gray-200 dark:text-gray-700"
                    viewBox="0 0 100 2"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <rect width="100" height="2" class="fill-current" />
                    <rect width="{{ $progressWidth }}" height="2" class="fill-amber-500" />
                </svg>
            </div>
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
                <x-filament::button tag="a" href="{{ $primaryCta['href'] }}" size="sm" color="primary">
                    {{ $primaryCta['label'] }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
@endif
