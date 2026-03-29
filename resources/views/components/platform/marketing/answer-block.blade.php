@props([
    'question' => '',
])
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-base font-semibold text-slate-900">{{ $question }}</h3>
    <div class="mt-2 text-sm leading-relaxed text-slate-600">
        {{ $slot }}
    </div>
</div>
