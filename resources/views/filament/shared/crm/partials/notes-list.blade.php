@php
    $notes = $getRecord()->notes()
        ->with('user')
        ->orderByDesc('id')
        ->get();
@endphp

<div class="crm-ws-notes flex flex-col gap-3">
    @if($notes->isEmpty())
        <div class="crm-ws-notes-empty rounded-xl bg-gray-50/70 px-4 py-6 text-center dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Пока нет заметок</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Добавьте первую заметку для команды</p>
        </div>
    @else
        <div class="space-y-2.5">
            @foreach($notes as $note)
                <div class="rounded-xl bg-gray-50/80 px-3 py-2.5 dark:bg-white/[0.04] @if($note->is_pinned) border-l-[3px] border-l-primary-500 @endif relative">
                    @if($note->is_pinned)
                        <div class="absolute -top-1.5 start-2 inline-flex items-center gap-0.5 rounded bg-primary-500/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary-700 dark:text-primary-300">
                            {!! svg('heroicon-s-bookmark', 'shrink-0', ['width' => '10', 'height' => '10', 'aria-hidden' => 'true'])->toHtml() !!}
                            Важное
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[9px] font-semibold text-gray-700 dark:bg-zinc-700 dark:text-gray-200">
                                {{ mb_substr($note->user?->name ?? '?', 0, 1) }}
                            </span>
                            <span class="truncate font-medium text-gray-800 dark:text-gray-100">{{ $note->user?->name ?? 'Система' }}</span>
                        </div>
                        <time class="shrink-0 tabular-nums" datetime="{{ $note->created_at->toIso8601String() }}">{{ $note->created_at->format('d.m.y H:i') }}</time>
                    </div>
                    <div class="mt-1.5 text-sm leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-wrap ps-7">
                        {{ $note->body }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
