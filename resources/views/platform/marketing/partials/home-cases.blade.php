<section id="primery" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-16 md:py-20" aria-labelledby="primery-heading">
    <div class="mx-auto max-w-6xl px-4 md:px-6">
        <h2 id="primery-heading" class="text-2xl font-bold text-slate-900 md:text-3xl">Примеры проектов</h2>
        <p class="mt-3 max-w-2xl text-slate-600">Только реальные кейсы или честные плейсхолдеры — без вымышленных брендов.</p>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach($pm['cases'] ?? [] as $case)
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="aspect-video rounded-lg bg-slate-100 ring-1 ring-slate-200/80"></div>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $case['title'] }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $case['type'] }}</p>
                    @if(!empty($case['url']) && !empty($case['real']))
                        <a href="{{ $case['url'] }}" class="mt-4 text-sm font-medium text-blue-700 hover:text-blue-800" rel="noopener noreferrer" target="_blank">Открыть сайт</a>
                    @else
                        <span class="mt-4 text-sm text-slate-500">Скоро</span>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
