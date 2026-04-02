@php
    if (! isset($attributes) || ! $attributes instanceof \Illuminate\View\ComponentAttributeBag) {
        $attributes = new \Illuminate\View\ComponentAttributeBag();
    }
    $showIcon = $showIcon ?? true;
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center font-bold tracking-tight group' . ($showIcon ? ' gap-0.5' : '')]) }}>
    @if($showIcon)
        <svg viewBox="0 0 64 64" class="h-8 w-8 text-pm-accent shrink-0 transition-transform duration-300 group-hover:scale-105" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="
            M 18 6
            H 38
            C 47.9 6 56 12.3 56 20
            C 56 27.7 47.9 34 38 34
            H 34
            L 26 42
            V 52
            C 26 55.3 23.3 58 20 58
            H 18
            C 14.7 58 12 55.3 12 52
            V 12
            C 12 8.7 14.7 6 18 6
            Z

            M 26 18
            H 38
            C 41.3 18 44 19.5 44 21
            C 44 22.5 41.3 24 38 24
            H 26
            V 18
            Z

            M 32 40
            H 40
            L 55.5 55.5
            C 56.5 56.5 56.5 58 55.5 59
            L 51 63.5
            C 50 64.5 48.5 64.5 47.5 63.5
            L 32 48
            V 40
            Z
        " />
        </svg>
    @endif
    <span class="text-xl font-bold tracking-tight text-slate-900 transition-colors dark:text-white">
        ent<span class="text-pm-accent relative">Base
            <span class="absolute -bottom-1 left-0 right-0 h-0.5 rounded-full bg-pm-accent/20"></span>
        </span>
    </span>
</div>
