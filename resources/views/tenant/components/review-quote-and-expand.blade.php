{{-- Полный текст в одном блоке; сжатие только при html.js + data-collapsed (см. resources/css/app.css). --}}
@props([
    'review',
    'scopeId' => 0,
    'quoteClass' => '',
    'readMoreClass' => 'text-[13px] font-semibold text-moto-amber underline-offset-4 hover:text-moto-amber/90 hover:underline',
    'openMark' => '"',
    'closeMark' => '"',
])
@php
    /** @var \App\Models\Review $review */
    $bodyId = 'review-body-'.$review->id.'-'.$scopeId;
    $wantsMore = $review->publicWantsReadMore();
    $fullPlain = $review->publicFullTextRaw();
@endphp
<div class="review-quote-expand" data-review-card>
    <div
        id="{{ e($bodyId) }}"
        class="review-quote-expand__body {{ $quoteClass }} whitespace-pre-wrap break-words"
        data-review-body
        @if ($wantsMore) data-collapsed="true" @endif
    >{{ $openMark }}{{ $fullPlain }}{{ $closeMark }}</div>
    @if ($wantsMore)
        <p class="mt-3 shrink-0">
            <button
                type="button"
                class="{{ $readMoreClass }}"
                data-review-toggle
                aria-controls="{{ e($bodyId) }}"
                aria-expanded="false"
            >Читать полностью</button>
        </p>
    @endif
</div>
