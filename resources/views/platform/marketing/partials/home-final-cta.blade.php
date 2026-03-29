<section id="cta-final" class="pm-section-anchor bg-blue-700 py-16 text-white md:py-20" aria-labelledby="cta-final-heading">
    <div class="mx-auto max-w-6xl px-4 text-center md:px-6">
        <h2 id="cta-final-heading" class="text-2xl font-bold md:text-3xl">{{ $pm['cta']['final_headline'] }}</h2>
        <p class="mx-auto mt-4 max-w-xl text-blue-100">Обсудим запуск, демо и формат сопровождения под вашу модель.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ Route::has('platform.contact') ? route('platform.contact') : url('/contact') }}" class="inline-flex rounded-lg bg-white px-6 py-3 text-sm font-semibold text-blue-800 hover:bg-blue-50">{{ $pm['cta']['primary'] }}</a>
            <a href="{{ platform_marketing_demo_url() }}" class="inline-flex rounded-lg border border-white/80 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">{{ $pm['cta']['secondary'] }}</a>
        </div>
    </div>
</section>
