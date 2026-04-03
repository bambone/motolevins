@php
    $h = $data['heading'] ?? '';
    $content = $data['content'] ?? '';
@endphp
<section class="prose prose-invert max-w-none text-sm text-silver prose-headings:text-white prose-p:leading-relaxed sm:text-base">
    @if(filled($h))
        <h2 class="text-balance text-xl font-bold text-white sm:text-2xl">{{ $h }}</h2>
    @endif
    @if(filled($content))
        {!! $content !!}
    @endif
</section>
