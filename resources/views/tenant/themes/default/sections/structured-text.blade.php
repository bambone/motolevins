@php
    $title = $data['title'] ?? null;
    $content = $data['content'] ?? '';
    $maxWidth = $data['max_width'] ?? 'prose';
    $widthClass = match ($maxWidth) {
        'wide' => 'max-w-3xl',
        'full' => 'max-w-none',
        default => 'max-w-prose',
    };
@endphp
<section class="{{ $widthClass }} mx-auto w-full min-w-0" data-page-section-type="{{ $section->section_type }}">
    @if(filled($title))
        <h2 class="mb-4 text-balance text-xl font-semibold text-white sm:text-2xl">{{ $title }}</h2>
    @endif
    @if(filled($content))
        <div class="prose prose-invert prose-sm max-w-none text-silver prose-headings:text-white prose-p:leading-relaxed sm:prose-base">
            {!! $content !!}
        </div>
    @endif
</section>
