<section id="dlya-kogo" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-12 sm:py-16 md:py-20" aria-labelledby="dlya-kogo-heading">
    <div class="mx-auto max-w-6xl px-3 sm:px-4 md:px-6">
        <h2 id="dlya-kogo-heading" class="text-balance text-xl font-bold leading-tight text-slate-900 sm:text-2xl md:text-3xl">Для&nbsp;кого RentBase</h2>
        <p class="mt-3 max-w-2xl text-pretty text-sm leading-relaxed text-slate-600 sm:text-base">Один продукт&nbsp;— разные модели сервиса. Найдите свой сегмент и&nbsp;результат, который получаете на&nbsp;платформе.</p>
        <div class="mt-8 grid gap-5 sm:mt-10 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
            @php
                $segments = [
                    [
                        'tag' => 'Прокат',
                        'title' => 'Прокат техники',
                        'desc' => 'Принимайте заявки и&nbsp;управляйте загрузкой парка без хаоса в&nbsp;чатах и&nbsp;таблицах.',
                        'icon' => '<svg class="w-6 h-6 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>'
                    ],
                    [
                        'tag' => 'Обучение',
                        'title' => 'Мастер-классы',
                        'desc' => 'Набирайте группы по&nbsp;расписанию и&nbsp;держите контакты в&nbsp;одной базе, а&nbsp;не&nbsp;в&nbsp;разных файлах.',
                        'icon' => '<svg class="w-6 h-6 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>'
                    ],
                    [
                        'tag' => 'Обучение',
                        'title' => 'Инструкторы',
                        'desc' => 'Клиенты сами записываются в&nbsp;свободные слоты&nbsp;— вы тратите меньше времени на&nbsp;переписку.',
                        'icon' => '<svg class="w-6 h-6 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>'
                    ],
                    [
                        'tag' => 'Сервис',
                        'title' => 'Услуги по&nbsp;записи',
                        'desc' => 'Замените поток в&nbsp;мессенджерах на&nbsp;календарь: кто, когда и&nbsp;на&nbsp;что записан&nbsp;— видно сразу.',
                        'icon' => '<svg class="w-6 h-6 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>'
                    ],
                ];
            @endphp
            @foreach($segments as $s)
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                    <p class="mb-3 text-[10px] font-bold uppercase tracking-wider text-pm-accent">{{ $s['tag'] }}</p>
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-pm-accent/10">
                        {!! $s['icon'] !!}
                    </div>
                    <h3 class="font-semibold text-slate-900">{{ $s['title'] }}</h3>
                    <p class="mt-2 text-pretty flex-1 text-sm leading-relaxed text-slate-600">{!! $s['desc'] !!}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
