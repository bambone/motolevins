<section id="control" class="pm-section-anchor border-b border-slate-200 bg-slate-900 py-16 text-white md:py-20" aria-labelledby="control-heading">
    <div class="mx-auto max-w-6xl px-4 md:px-6">
        <h2 id="control-heading" class="text-2xl font-bold md:text-3xl">Вы контролируете весь бизнес в одном месте</h2>
        <p class="mt-4 max-w-2xl text-slate-300">Операционный слой для владельца: не разрозненные таблицы, а единый контур заявок и клиентов.</p>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(['Заявки', 'Клиенты', 'Загрузка и занятость', 'Статусы сделок', 'История взаимодействий'] as $item)
                <li class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-sm font-medium">{{ $item }}</li>
            @endforeach
        </ul>
    </div>
</section>
