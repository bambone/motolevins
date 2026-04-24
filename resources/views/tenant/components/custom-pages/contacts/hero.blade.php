@props([
    'title' => 'Контакты',
    /** Нейтральный лид: без привязки к нише; темы (напр. moto) могут передать свой текст. */
    'intro' => 'Мы на связи. Выберите удобный способ связи — позвоните, напишите или оставьте заявку на сайте.',
])
@php
    use App\Support\Typography\RussianTypography;

    $introTied = RussianTypography::tiePrepositionsToNextWord($intro);
@endphp

<section class="relative z-10 pt-28 pb-12 sm:pt-36 sm:pb-16 bg-[#0c0c0e] border-b border-white/[0.02]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 md:px-8 text-center">
        <h1 class="mb-4 text-balance text-3xl font-bold leading-tight text-white sm:mb-6 sm:text-4xl md:text-5xl">
            {{ RussianTypography::tiePrepositionsToNextWord($title) }}
        </h1>
        <p class="max-w-2xl mx-auto text-sm leading-relaxed text-silver/80 sm:text-base md:text-lg">
            {{ $introTied }}
        </p>
    </div>
</section>
