@php
    $meta = $block['meta'] ?? [];
@endphp
<section class="tenant-site-footer-moto__cta mb-8 rounded-[1.25rem] border border-white/[0.08] bg-[rgb(18_22_28)]/95 p-6 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] sm:p-8">
    @if(filled($meta['headline'] ?? ''))
        <h3 class="text-balance text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $meta['headline'] }}</h3>
    @endif
    @if(filled($meta['subheadline'] ?? ''))
        <p class="mt-3 max-w-prose text-pretty text-sm leading-relaxed text-white/75">{{ \App\Support\Typography\RussianTypography::tiePrepositionsToNextWord((string) $meta['subheadline']) }}</p>
    @endif
    <div class="mt-6 flex flex-wrap gap-3">
        @if(filled($meta['primary_button_label'] ?? '') && filled($meta['primary_button_url'] ?? ''))
            <a href="{{ $meta['primary_button_url'] }}" class="tenant-btn-primary inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold">{{ $meta['primary_button_label'] }}</a>
        @endif
        @if(filled($meta['secondary_button_label'] ?? '') && filled($meta['secondary_button_url'] ?? ''))
            <a href="{{ $meta['secondary_button_url'] }}" target="_blank" rel="noopener noreferrer" class="tenant-btn-secondary inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold">{{ $meta['secondary_button_label'] }}</a>
        @endif
    </div>
</section>
