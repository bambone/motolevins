@php
    $heading = $data['heading'] ?? '';
    $body = $data['body'] ?? '';
    $btn = $data['button_text'] ?? '';
    $url = $data['button_url'] ?? '#';
    $btn2 = $data['secondary_button_text'] ?? '';
    $url2 = $data['secondary_button_url'] ?? '#';
@endphp
<section
    class="relative w-full min-w-0 overflow-hidden rounded-[1.75rem] border border-[rgb(28_31_38)]/[0.12] bg-gradient-to-br from-[#14161f] via-[#0c0e14] to-[#07080c] px-6 py-10 shadow-[0_40px_100px_-44px_rgba(0,0,0,0.75)] ring-1 ring-inset ring-white/[0.06] sm:px-10 sm:py-12 lg:px-12 lg:py-14"
    data-page-section-type="{{ $section->section_type ?? '' }}"
>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-moto-amber/40 to-transparent" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 top-1/2 h-[min(24rem,70vw)] w-[min(24rem,70vw)] -translate-y-1/2 rounded-full bg-moto-amber/[0.07] blur-3xl" aria-hidden="true"></div>

    <div class="relative z-10 grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-12">
        <div class="min-w-0 lg:col-span-7 xl:col-span-8">
            @if(filled($heading))
                <h2 class="max-w-2xl text-balance text-2xl font-bold leading-tight tracking-tight text-white sm:text-[1.65rem] md:text-3xl">{{ $heading }}</h2>
            @endif
            @if(filled($body))
                <p class="mt-4 max-w-2xl text-pretty text-[1.05rem] leading-relaxed text-[rgb(210_214_222)] sm:text-[1.125rem]">{{ $body }}</p>
            @endif
        </div>
        @if(filled($btn) || filled($btn2))
            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:flex-wrap lg:col-span-5 xl:col-span-4 lg:flex-col lg:items-stretch xl:items-end">
                @if(filled($btn))
                    <a
                        href="{{ e($url) }}"
                        data-rb-advocate-cta-link="primary"
                        class="inline-flex min-h-[3.25rem] min-w-[12rem] flex-1 items-center justify-center rounded-xl bg-moto-amber px-7 py-3.5 text-center text-[0.95rem] font-bold uppercase tracking-wide text-[#0c0c0e] shadow-lg shadow-black/40 transition hover:bg-[#b08a58] focus-visible:outline focus-visible:ring-2 focus-visible:ring-moto-amber/80 sm:text-sm"
                    >{{ $btn }}</a>
                @endif
                @if(filled($btn2))
                    <a
                        href="{{ e($url2) }}"
                        data-rb-advocate-cta-link="secondary"
                        class="inline-flex min-h-[3.25rem] min-w-[12rem] flex-1 items-center justify-center rounded-xl border border-white/20 bg-white/[0.07] px-7 py-3.5 text-center text-[0.95rem] font-bold uppercase tracking-wide text-white/95 transition hover:border-moto-amber/45 hover:bg-white/[0.11] focus-visible:outline focus-visible:ring-2 focus-visible:ring-white/30 sm:text-sm"
                    >{{ $btn2 }}</a>
                @endif
            </div>
        @endif
    </div>
</section>
