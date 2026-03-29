<section id="hero" class="pm-section-anchor border-b border-slate-200 bg-gradient-to-b from-white to-slate-50" aria-labelledby="hero-heading">
    <div class="mx-auto max-w-6xl px-4 py-16 md:px-6 md:py-24">
        <h1 id="hero-heading" class="max-w-3xl text-3xl font-bold tracking-tight text-slate-900 md:text-4xl lg:text-5xl">
            {{ platform_marketing_hero_headline() }}
        </h1>
        <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ $pm['hero_subtitle'] }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ Route::has('platform.contact') ? route('platform.contact') : url('/contact') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">{{ $pm['cta']['primary'] }}</a>
            <a href="{{ platform_marketing_demo_url() }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ $pm['cta']['secondary'] }}</a>
        </div>
    </div>
</section>
