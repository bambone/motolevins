@props([
    'title' => 'Условия аренды',
    'subtitle' => 'Прозрачные правила для вашей безопасности и комфорта',
])
@php
    use App\Support\Typography\RussianTypography;
@endphp

<section class="relative z-10 pt-28 pb-10 sm:pt-36 sm:pb-14 bg-[#0c0c0e] border-b border-white/[0.02]">
    <div class="mx-auto max-w-5xl px-3 sm:px-4 md:px-8">
        <h1 class="mb-4 text-balance text-3xl font-bold leading-tight text-white sm:mb-6 sm:text-4xl md:text-5xl">
            {{ RussianTypography::tiePrepositionsToNextWord($title) }}
        </h1>
        <p class="max-w-2xl text-sm leading-relaxed text-silver/80 sm:text-base md:text-lg">
            {{ RussianTypography::tiePrepositionsToNextWord($subtitle) }}
        </p>
    </div>
</section>
