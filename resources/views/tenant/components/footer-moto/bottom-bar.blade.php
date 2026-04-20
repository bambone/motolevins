@php
    $meta = $block['meta'] ?? [];
@endphp
<div class="mt-8 flex flex-col gap-3 border-t border-white/[0.06] pt-6 text-[12px] leading-relaxed text-white/50 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <p class="text-pretty text-white/60">
        @if(filled($meta['copyright_text'] ?? ''))
            {{ $meta['copyright_text'] }}
        @else
            © {{ $year }} {{ $siteName }}
        @endif
    </p>
    @if(filled($meta['secondary_text'] ?? ''))
        <p class="text-pretty max-w-prose text-[12px] text-white/45">{{ $meta['secondary_text'] }}</p>
    @elseif(filled($f['footer_tagline'] ?? ''))
        <p class="text-pretty max-w-prose text-[12px] text-white/45">{{ \App\Support\Typography\RussianTypography::tiePrepositionsToNextWord((string) $f['footer_tagline']) }}</p>
    @endif
</div>
