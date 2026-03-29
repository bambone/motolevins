<section id="dlya-kogo" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-16 md:py-20" aria-labelledby="dlya-kogo-heading">
    <div class="mx-auto max-w-6xl px-4 md:px-6">
        <h2 id="dlya-kogo-heading" class="text-2xl font-bold text-slate-900 md:text-3xl">Для кого RentBase</h2>
        <p class="mt-3 max-w-2xl text-slate-600">Один продукт — разные сервисные модели. Фокус на бизнесе с записями, слотами и бронированиями.</p>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Прокат техники', 'Мото, авто, спецтехника — каталог, слоты, заявки.'],
                ['Курсы и мастер-классы', 'Расписание, набор групп, оплата и коммуникации в одном контуре.'],
                ['Инструкторы и тренеры', 'Личное расписание, записи клиентов, история занятий.'],
                ['Услуги по записи', 'Салоны, сервисы, консультации — онлайн-запись без хаоса в мессенджерах.'],
            ] as [$title, $text])
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
