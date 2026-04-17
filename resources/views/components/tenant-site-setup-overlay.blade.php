@props(['payload' => null])

@if($payload)
    @php
        $actionUrl = $payload['session_action_url'] ?? null;
        $canSnooze = ! empty($payload['can_snooze']);
        $canNotNeeded = ! empty($payload['can_not_needed']);
        $launchCritical = ! empty($payload['launch_critical']);
    @endphp
    <div
        id="tenant-site-setup-bar"
        class="fi-ts-setup-bar fixed left-0 right-0 top-14 z-[35] border-b border-amber-200 px-3 py-2.5 text-sm shadow-sm sm:top-16 sm:px-4"
        role="region"
        aria-label="Мастер настройки сайта"
    >
        <div class="mx-auto flex max-w-7xl flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div class="min-w-0 text-amber-950 dark:text-amber-50">
                <span class="font-semibold">Мастер настройки:</span>
                {{ $payload['current_title'] ?? '' }}
                @if(!empty($payload['steps_total']))
                    <span class="text-amber-800 dark:text-amber-200">
                        (шаг {{ ($payload['step_index'] ?? 0) + 1 }} из {{ $payload['steps_total'] }})
                    </span>
                @endif
                @if($launchCritical)
                    <span class="ml-1 text-xs font-semibold text-amber-900 dark:text-amber-100">· критично для запуска</span>
                @endif
            </div>
            <div class="fi-ts-setup-actions flex flex-wrap items-center gap-1.5 sm:gap-2">
                @if($actionUrl)
                    <form method="post" action="{{ $actionUrl }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="next" />
                        <button type="submit" class="fi-ts-setup-btn fi-ts-setup-btn-primary">
                            <span class="fi-ts-setup-btn-label">Дальше</span>
                        </button>
                    </form>
                    <form method="post" action="{{ $actionUrl }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="snooze" />
                        <button
                            type="submit"
                            @disabled(! $canSnooze)
                            class="fi-ts-setup-btn fi-ts-setup-btn-secondary {{ $canSnooze ? '' : 'fi-ts-setup-btn-disabled' }}"
                            aria-label="Отложить шаг"
                        >
                            <span class="fi-ts-setup-btn-label">Позже</span>
                        </button>
                    </form>
                    <form method="post" action="{{ $actionUrl }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="not_needed" />
                        <button
                            type="submit"
                            @disabled(! $canNotNeeded)
                            class="fi-ts-setup-btn fi-ts-setup-btn-secondary {{ $canNotNeeded ? '' : 'fi-ts-setup-btn-disabled' }}"
                            aria-label="Не требуется для проекта"
                        >
                            <span class="fi-ts-setup-btn-label">Не требуется</span>
                        </button>
                    </form>
                    <form method="post" action="{{ $actionUrl }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="pause" />
                        <button type="submit" class="fi-ts-setup-btn fi-ts-setup-btn-ghost">
                            <span class="fi-ts-setup-btn-label">Пауза</span>
                        </button>
                    </form>
                @endif
                <a
                    href="{{ \App\Filament\Tenant\Pages\TenantSiteSetupCenterPage::getUrl() }}"
                    class="fi-ts-setup-btn fi-ts-setup-btn-accent inline-flex items-center justify-center no-underline"
                >
                    <span class="fi-ts-setup-btn-label">Центр</span>
                </a>
            </div>
        </div>
    </div>
    <script type="application/json" id="tenant-site-setup-payload">@json($payload)</script>
@endif
