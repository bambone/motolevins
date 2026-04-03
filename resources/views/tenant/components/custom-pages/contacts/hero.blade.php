@props([
    'title' => 'Контакты',
])

<section class="relative z-10 pt-28 pb-12 sm:pt-36 sm:pb-16 bg-[#0c0c0e] border-b border-white/[0.02]">
    <div class="mx-auto max-w-4xl px-3 sm:px-4 md:px-8 text-center">
        <h1 class="mb-4 text-balance text-3xl font-bold leading-tight text-white sm:mb-6 sm:text-4xl md:text-5xl">
            {{ $title }}
        </h1>
        <p class="max-w-2xl mx-auto text-sm leading-relaxed text-silver/80 sm:text-base md:text-lg">
            Мы всегда на связи. Выберите удобный способ общения или приезжайте в наш прокат.
        </p>
    </div>
</section>
