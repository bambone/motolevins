@php
    $activities = $getRecord()->activities()
        ->with('actor')
        ->orderByDesc('id')
        ->get();
@endphp

<div class="crm-ws-activity flex flex-col gap-1">
    @if($activities->isEmpty())
        <div class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">Событий пока нет</div>
    @else
        <div class="crm-ws-activity-rail relative space-y-2.5">
            @foreach($activities as $activity)
                @php
                    $isImportant = in_array($activity->type, [
                        \App\Models\CrmRequestActivity::TYPE_STATUS_CHANGED, 
                        \App\Models\CrmRequestActivity::TYPE_PRIORITY_CHANGED
                    ]);
                    $iconColor = match ($activity->type) {
                        \App\Models\CrmRequestActivity::TYPE_INBOUND_RECEIVED => 'text-primary-500 bg-primary-50 dark:bg-primary-500/10',
                        \App\Models\CrmRequestActivity::TYPE_STATUS_CHANGED => 'text-success-500 bg-success-50 dark:bg-success-500/10',
                        \App\Models\CrmRequestActivity::TYPE_NOTE_ADDED => 'text-amber-500 bg-amber-50 dark:bg-amber-500/10',
                        \App\Models\CrmRequestActivity::TYPE_MAIL_QUEUED => 'text-info-500 bg-info-50 dark:bg-info-500/10',
                        \App\Models\CrmRequestActivity::TYPE_PRIORITY_CHANGED => 'text-orange-500 bg-orange-50 dark:bg-orange-500/10',
                        \App\Models\CrmRequestActivity::TYPE_FOLLOW_UP_SET => 'text-violet-500 bg-violet-50 dark:bg-violet-500/10',
                        \App\Models\CrmRequestActivity::TYPE_SUMMARY_UPDATED => 'text-teal-500 bg-teal-50 dark:bg-teal-500/10',
                        \App\Models\CrmRequestActivity::TYPE_ASSIGNED => 'text-sky-500 bg-sky-50 dark:bg-sky-500/10',
                        default => 'text-gray-500 bg-gray-100 dark:bg-zinc-800',
                    };
                    $icon = match ($activity->type) {
                        \App\Models\CrmRequestActivity::TYPE_INBOUND_RECEIVED => 'heroicon-o-inbox-arrow-down',
                        \App\Models\CrmRequestActivity::TYPE_STATUS_CHANGED => 'heroicon-o-arrow-path',
                        \App\Models\CrmRequestActivity::TYPE_NOTE_ADDED => 'heroicon-o-document-text',
                        \App\Models\CrmRequestActivity::TYPE_MAIL_QUEUED => 'heroicon-o-envelope',
                        \App\Models\CrmRequestActivity::TYPE_PRIORITY_CHANGED => 'heroicon-o-exclamation-triangle',
                        \App\Models\CrmRequestActivity::TYPE_FOLLOW_UP_SET => 'heroicon-o-bell-alert',
                        \App\Models\CrmRequestActivity::TYPE_SUMMARY_UPDATED => 'heroicon-o-clipboard-document-check',
                        \App\Models\CrmRequestActivity::TYPE_ASSIGNED => 'heroicon-o-user-circle',
                        default => 'heroicon-o-clock',
                    };
                @endphp
                
                <div class="crm-ws-activity-row relative pb-1 pl-5">
                    <div class="crm-ws-activity-dot absolute -left-[13px] top-0.5 flex h-6 w-6 items-center justify-center rounded-full {{ $iconColor }}">
                        {!! svg($icon, 'pointer-events-none shrink-0', ['width' => '13', 'height' => '13', 'aria-hidden' => 'true'])->toHtml() !!}
                    </div>

                    <div class="flex flex-col gap-0.5">
                        <div class="text-sm font-medium leading-snug {{ $isImportant ? 'text-gray-900 dark:text-gray-100' : 'text-gray-800 dark:text-gray-200' }}">
                            {{ \App\Models\CrmRequestActivity::typeLabel($activity->type) }}
                        </div>
                        <div class="text-[11px] leading-tight text-gray-500 dark:text-gray-500">
                            <span class="tabular-nums">{{ $activity->created_at->format('d.m.y H:i') }}</span>
                            @if($activity->actor)
                                <span> · {{ $activity->actor->name }}</span>
                            @endif
                        </div>

                        @if($activity->meta)
                            <div class="mt-1 text-[13px] leading-relaxed text-gray-600 dark:text-gray-400">
                                @if($activity->type === \App\Models\CrmRequestActivity::TYPE_STATUS_CHANGED)
                                    Изменен с 
                                    <span class="font-medium">{{ \App\Models\CrmRequest::statusLabels()[$activity->meta['old']] ?? $activity->meta['old'] }}</span> 
                                    на 
                                    <span class="font-medium">{{ \App\Models\CrmRequest::statusLabels()[$activity->meta['new']] ?? $activity->meta['new'] }}</span>
                                @elseif($activity->type === \App\Models\CrmRequestActivity::TYPE_PRIORITY_CHANGED)
                                    Изменен с 
                                    <span class="font-medium">{{ \App\Models\CrmRequest::priorityLabels()[$activity->meta['old']] ?? $activity->meta['old'] }}</span> 
                                    на 
                                    <span class="font-medium">{{ \App\Models\CrmRequest::priorityLabels()[$activity->meta['new']] ?? $activity->meta['new'] }}</span>
                                @elseif($activity->type === \App\Models\CrmRequestActivity::TYPE_NOTE_ADDED || $activity->type === \App\Models\CrmRequestActivity::TYPE_SUMMARY_UPDATED)
                                    <span class="italic text-gray-500 dark:text-gray-400 break-words line-clamp-2">"{{ $activity->meta['preview'] ?? '...' }}"</span>
                                @elseif($activity->type === \App\Models\CrmRequestActivity::TYPE_FOLLOW_UP_SET)
                                    @php
                                        $followUpAt = $activity->meta['at'] ?? null;
                                    @endphp
                                    @if(is_string($followUpAt) && $followUpAt !== '')
                                        Назначено на <span class="font-medium">{{ \Carbon\Carbon::parse($followUpAt)->format('d.m.Y H:i') }}</span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">{{ $activity->summaryLine() }}</span>
                                    @endif
                                @elseif(in_array($activity->type, [
                                    \App\Models\CrmRequestActivity::TYPE_INBOUND_RECEIVED,
                                    \App\Models\CrmRequestActivity::TYPE_MAIL_QUEUED,
                                    \App\Models\CrmRequestActivity::TYPE_ASSIGNED,
                                ], true))
                                    <span class="text-gray-600 dark:text-gray-400">{{ $activity->summaryLine() }}</span>
                                @else
                                    <pre class="bg-gray-50 dark:bg-white/5 p-2 rounded-lg text-[10px] overflow-x-auto text-gray-600 dark:text-gray-400">{{ json_encode($activity->meta, JSON_UNESCAPED_UNICODE) }}</pre>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
