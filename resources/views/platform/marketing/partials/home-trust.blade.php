@php
    $t = $pm['trust'] ?? [];
@endphp
<section id="doverie" class="pm-section-anchor border-b border-slate-200 bg-white" aria-label="Показатели доверия">
    <div class="mx-auto max-w-6xl px-3 py-5 sm:px-4 sm:py-6 md:px-6">
        <div class="flex flex-col gap-6 text-center text-sm text-slate-600 sm:flex-row sm:flex-wrap sm:items-start sm:justify-center sm:gap-10 md:justify-start md:text-left">
            <div class="max-w-xs sm:max-w-[220px]">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $t['applications_eyebrow'] ?? 'Система в работе' }}</span>
                <span class="block text-2xl font-bold text-slate-900">{{ $t['applications'] }}</span>
                <span class="mt-1 block font-medium leading-snug text-slate-800">{{ $t['applications_line'] ?? 'заявок — система реально используется в бизнесе' }}</span>
            </div>
            <div class="hidden h-8 w-px shrink-0 self-center bg-slate-200 sm:block" aria-hidden="true"></div>
            <div class="max-w-xs sm:max-w-[220px]">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $t['businesses_eyebrow'] ?? 'Проекты на платформе' }}</span>
                <span class="block text-2xl font-bold text-slate-900">{{ $t['businesses'] }}</span>
                <span class="mt-1 block font-medium leading-snug text-slate-800">{{ $t['businesses_line'] ?? 'бизнесов — живые проекты, не тестовые площадки' }}</span>
            </div>
        </div>
    </div>
</section>
