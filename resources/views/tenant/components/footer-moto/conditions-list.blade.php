@php
    $meta = $block['meta'] ?? [];
@endphp
<section class="mb-8">
    @if(filled($meta['headline'] ?? ''))
        <h3 class="text-lg font-semibold text-white">{{ $meta['headline'] }}</h3>
    @endif
    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-white/80">
        @foreach($meta['items'] ?? [] as $line)
            <li class="text-pretty">{{ \App\Support\Typography\RussianTypography::tiePrepositionsToNextWord((string) $line) }}</li>
        @endforeach
    </ul>
</section>
