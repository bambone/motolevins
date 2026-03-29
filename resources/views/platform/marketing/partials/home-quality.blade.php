<section id="uroven-produkta" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-16 md:py-20" aria-labelledby="uroven-heading">
    <div class="mx-auto max-w-6xl px-4 md:px-6">
        <h2 id="uroven-heading" class="text-2xl font-bold text-slate-900 md:text-3xl">Продукт для бизнеса, а не для экспериментов</h2>
        <p class="mt-4 max-w-3xl text-slate-600">Мы сознательно не строим модель «бесплатно для всех»: это про уровень продукта, качество аудитории и устойчивую инфраструктуру.</p>
        <ul class="mt-8 grid gap-4 md:grid-cols-2">
            @foreach([
                'К вам приходят компании, которые готовы работать серьёзно — без «потестить за ноль».',
                'Меньше шума в поддержке и больше фокуса на развитии функций для платящих клиентов.',
                'Стабильные серверы, бэкапы и предсказуемая работа сервиса.',
                'Человеческая поддержка и сопровождение на старте.',
            ] as $line)
                <li class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ $line }}</li>
            @endforeach
        </ul>
    </div>
</section>
