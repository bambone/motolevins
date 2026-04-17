<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $next = $this->nextPendingStep;
        $nextKey = is_array($next) ? ($next['key'] ?? null) : null;
        $nextDef = is_string($nextKey) ? (\App\TenantSiteSetup\SetupItemRegistry::definitions()[$nextKey] ?? null) : null;
        $launchCta = $this->launchPrimaryCta;
        $profileUrl = \App\Filament\Tenant\Pages\TenantSiteSetupProfilePage::getUrl();
    @endphp

    <div class="space-y-8">
        @if(session()->has('site_setup_guided_completed'))
            @php
                $guidedDone = session('site_setup_guided_completed');
            @endphp
            <div
                class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 text-sm text-emerald-950 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-50"
                role="status"
            >
                @if($guidedDone === 'base_launch')
                    <p class="font-semibold">Базовый запуск по чеклисту завершён.</p>
                    <p class="mt-1 text-emerald-900/90 dark:text-emerald-100/90">
                        Все пункты быстрого запуска отмечены. Дальше можно усилить публичный контур и расширенные настройки.
                    </p>
                @else
                    <p class="font-semibold">Очередь guided завершена.</p>
                    <p class="mt-1 text-emerald-900/90 dark:text-emerald-100/90">
                        Переходите к следующим шагам чеклиста или к разделам кабинета по необходимости.
                    </p>
                @endif
            </div>
        @endif

        {{-- 1. Верхний action-блок (порядок зафиксирован в ТЗ) --}}
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/40 sm:p-6">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Быстрый запуск</h2>
            <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                @if($this->hasPausedSession)
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-amber-950 dark:bg-amber-900/50 dark:text-amber-100">Сессия на паузе</span>
                @elseif($this->hasActiveSession)
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-emerald-950 dark:bg-emerald-900/40 dark:text-emerald-100">Сессия активна</span>
                @else
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-gray-800 dark:bg-gray-800 dark:text-gray-200">Нет активной сессии</span>
                @endif
            </div>

            @if(is_array($next) && !empty($next['title']))
                <div class="mt-4 space-y-1">
                    <p class="text-sm font-medium text-gray-950 dark:text-white">Следующий шаг: {{ $next['title'] }}</p>
                    @if($nextDef)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $nextDef->description }}</p>
                    @endif
                    @if(!empty($next['url']))
                        <div class="pt-1">
                            <a
                                href="{{ $next['url'] }}"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 underline decoration-primary-600/30 hover:decoration-primary-600 dark:text-primary-400"
                            >
                                Перейти к шагу
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <x-filament::button tag="a" :href="$launchCta['href']" color="primary" icon="heroicon-o-play">
                    {{ $launchCta['label'] }}
                </x-filament::button>
                <x-filament::button tag="a" :href="$profileUrl" color="gray" outlined icon="heroicon-o-clipboard-document-list">
                    Профиль сайта
                </x-filament::button>
            </div>
            @if($this->hasPausedSession)
                <p class="mt-4 text-sm text-amber-950 dark:text-amber-100/90">
                    На паузе — нажмите «Продолжить запуск» или «Новая очередь» в шапке страницы, чтобы пересобрать шаги.
                </p>
            @endif
        </section>

        {{-- 2. Прогресс --}}
        <x-filament::section>
            <x-slot name="heading">Прогресс</x-slot>
            <x-slot name="description">
                Считается по чеклисту запуска в мастере, не по всем разделам кабинета. Расширенный контур сейчас небольшой и будет пополняться в реестре.
            </x-slot>
            @php
                $qPct = (int) ($summary['quick_launch_percent'] ?? 0);
                $ePct = (int) ($summary['extended_percent'] ?? 0);
                $qA = (int) ($summary['quick_launch_applicable'] ?? 0);
                $eA = (int) ($summary['extended_applicable'] ?? 0);
                $qC = (int) ($summary['quick_launch_completed'] ?? 0);
                $eC = (int) ($summary['extended_completed'] ?? 0);
            @endphp
            @if(($qA + $eA) > 0)
                <div class="grid gap-4 sm:grid-cols-2">
                    @if($qA > 0)
                        <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">Быстрый запуск</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                                {{ $qC }} из {{ $qA }}
                                <span class="text-base font-normal text-gray-500 dark:text-gray-400">({{ $qPct }}%)</span>
                            </p>
                        </div>
                    @endif
                    @if($eA > 0)
                        <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                            <p class="text-sm font-medium text-gray-950 dark:text-white">Расширенный запуск</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                                {{ $eC }} из {{ $eA }}
                                <span class="text-base font-normal text-gray-500 dark:text-gray-400">({{ $ePct }}%)</span>
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Не исчерпывает остальные блоки панели.</p>
                        </div>
                    @endif
                </div>
            @endif
            <div class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50/80 p-3 dark:border-gray-700 dark:bg-gray-900/30">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-semibold text-gray-950 dark:text-white">{{ $summary['completion_percent'] ?? 0 }}%</span>
                    — сводка по всем пунктам текущего чеклиста
                    ({{ $summary['completed_count'] ?? 0 }} из {{ $summary['applicable_count'] ?? 0 }}).
                </p>
            </div>
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                Критично для запуска: осталось {{ $summary['launch_critical_remaining'] ?? 0 }} из {{ $summary['launch_critical_total'] ?? 0 }}
            </p>
        </x-filament::section>

        {{-- 3. Смысловые группы --}}
        @foreach($this->uiGroupSections as $section)
            @if(!empty($section['items']))
                <x-filament::section>
                    <x-slot name="heading">{{ $section['label'] }}</x-slot>
                    <ul class="space-y-4">
                        @foreach($section['items'] as $item)
                            <li class="rounded-lg border border-gray-100 p-3 dark:border-gray-800 sm:flex sm:items-start sm:justify-between sm:gap-4">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $item['title'] }}</span>
                                        @if(!empty($item['is_done']))
                                            <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Готово</span>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['execution_label'] }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item['description'] }}</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">Сейчас: {{ $item['snapshot'] }}</p>
                                </div>
                                <div class="mt-3 shrink-0 sm:mt-0">
                                    @if(!empty($item['url']) && empty($item['is_done']))
                                        <x-filament::button tag="a" href="{{ $item['url'] }}" size="sm" color="gray">
                                            Перейти
                                        </x-filament::button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
        @endforeach

        {{-- 4. Расширенные сценарии (короткие ссылки) --}}
        <x-filament::section>
            <x-slot name="heading">Расширенные настройки</x-slot>
            <x-slot name="description">После базового запуска — SEO, домен, уведомления и др.</x-slot>
            <ul class="list-inside list-disc space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <li>
                    <a href="{{ \App\Filament\Tenant\Pages\SeoFiles::getUrl() }}" class="font-medium text-primary-600 underline dark:text-primary-400">SEO и файлы для поисковиков</a>
                </li>
                <li>
                    <a href="{{ \App\Filament\Tenant\Pages\Settings::getUrl() }}" class="font-medium text-primary-600 underline dark:text-primary-400">Настройки сайта</a>
                </li>
                <li>
                    <a href="{{ \App\Filament\Tenant\Resources\CustomDomainResource::getUrl() }}" class="font-medium text-primary-600 underline dark:text-primary-400">Домены</a>
                </li>
            </ul>
        </x-filament::section>

        {{-- 5. Все пункты --}}
        <x-filament::section>
            <x-slot name="heading">Все пункты</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">Пункт</th>
                            <th class="py-2 pr-4">Статус</th>
                            <th class="py-2">Сейчас</th>
                            <th class="py-2 pr-2 text-right">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->categoryRows as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
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
                                    @elseif(!empty($row['url']))
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="text-xs font-semibold text-primary-600 underline dark:text-primary-400"
                                        >
                                            Открыть
                                        </a>
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

        <p class="text-sm text-gray-600 dark:text-gray-400">
            <a
                href="{{ $profileUrl }}"
                class="font-medium text-primary-600 underline decoration-primary-600/30 hover:decoration-primary-600 dark:text-primary-400"
            >
                Профиль сайта
            </a>
            — цели и контекст проекта (необязательно; помогает приоритизировать шаги).
        </p>
    </div>
</x-filament-panels::page>
