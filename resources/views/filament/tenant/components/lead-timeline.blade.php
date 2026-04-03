@php
    $logs = $getRecord() ? $getRecord()->activityLogs()->with('actor')->get() : [];
@endphp

<div class="fi-lead-timeline-wrap">
    <ul class="fi-lead-timeline m-0 list-none p-0" role="list">
        @forelse($logs as $log)
            <li class="fi-lead-timeline__row grid grid-cols-[3.5rem_minmax(0,1fr)] gap-x-6 sm:grid-cols-[3.75rem_minmax(0,1fr)] sm:gap-x-8">
                <div class="fi-lead-timeline__rail flex min-h-0 flex-col items-stretch">
                    @if (! $loop->first)
                        {{-- Сегмент к более новому событию (выше): линия + стрелка вверх (от старого к новому) --}}
                        <div
                            class="fi-lead-timeline__segment fi-lead-timeline__segment--to-newer relative flex min-h-[1.25rem] w-full flex-1 flex-col items-center"
                            aria-hidden="true"
                        >
                            <span class="fi-lead-timeline__line-v"></span>
                            <span class="fi-lead-timeline__arrow-up"></span>
                        </div>
                    @else
                        <div class="fi-lead-timeline__rail-spacer h-2 w-full shrink-0" aria-hidden="true"></div>
                    @endif

                    <div
                        class="fi-lead-timeline__node relative z-[2] mx-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:shadow-none"
                    >
                        @if($log->type === 'status_change')
                            <x-crm.svg-icon name="heroicon-o-arrow-path" size="md" class="text-primary-500" />
                        @elseif($log->type === 'call_made')
                            <x-crm.svg-icon name="heroicon-o-phone" size="md" class="text-gray-500 dark:text-gray-400" />
                        @elseif($log->type === 'whatsapp_sent')
                            <x-crm.svg-icon name="heroicon-o-chat-bubble-left" size="md" class="text-green-600 dark:text-green-400" />
                        @elseif($log->type === 'reverted')
                            <x-crm.svg-icon name="heroicon-o-arrow-uturn-left" size="md" class="text-danger-500" />
                        @else
                            <x-crm.svg-icon name="heroicon-o-chat-bubble-bottom-center-text" size="md" class="text-gray-500 dark:text-gray-400" />
                        @endif
                    </div>

                    @if (! $loop->last)
                        <div
                            class="fi-lead-timeline__segment fi-lead-timeline__segment--to-older relative flex min-h-[1.25rem] w-full flex-1 flex-col items-center"
                            aria-hidden="true"
                        >
                            <span class="fi-lead-timeline__line-v"></span>
                        </div>
                    @endif
                </div>

                <div @class([
                    'fi-lead-timeline__body min-w-0 pt-0.5',
                    'pb-1' => $loop->last,
                    'pb-6' => ! $loop->last,
                ])>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        @if($log->type === 'status_change')
                            Статус изменен на «{{ \App\Models\Lead::statuses()[$log->payload['new_status'] ?? ''] ?? 'Неизвестно' }}»
                        @elseif($log->type === 'reverted')
                            Действие отменено (возврат к «{{ \App\Models\Lead::statuses()[$log->payload['new_status'] ?? ''] ?? '' }}»)
                        @else
                            {{ $log->comment ?: 'Действие' }}
                        @endif
                    </div>
                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $log->created_at->format('d.m.Y H:i') }} •
                        @if($log->actor)
                            {{ $log->actor->name }}
                        @else
                            Система
                        @endif
                    </div>
                    @if($log->type === 'status_change' && $log->comment)
                        <div class="mt-2 rounded bg-gray-50 p-2 text-xs text-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
                            {{ $log->comment }}
                        </div>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm italic text-gray-500 dark:text-gray-400">Истории пока нет</li>
        @endforelse
    </ul>
</div>
