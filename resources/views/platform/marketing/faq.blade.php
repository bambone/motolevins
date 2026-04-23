@extends('platform.layouts.marketing')

@section('title', 'FAQ')

@section('meta_description')
Ответы на вопросы о RentBase: подойдёт ли вашему бизнесу, отличие от WordPress, сроки запуска, демо, что после заявки, переход с базового на кастомный тариф.
@endsection

@php
    $pm = app(\App\Product\Settings\MarketingContentResolver::class)->resolved();
    $base = platform_marketing_canonical_origin() ?: request()->getSchemeAndHttpHost();
    $faqs = [
        ['Подойдёт ли RentBase для моего бизнеса?', 'Если вам нужны онлайн-запись или бронирования, заявки, клиенты и админка в одном контуре — скорее да. Прокат, курсы, инструкторы, сервисы по записи — типичные сценарии. Напишите нишу — скажем честно, насколько это совпадает с продуктом.'],
        ['Чем это отличается от WordPress?', 'WordPress — это сайт и плагины; под запись и заявки часто приходится собирать цепочку вручную. RentBase — готовый продукт под сервисный бизнес: слоты, заявки, статусы и клиенты уже в одной логике, без «склейки» десяти расширений.'],
        ['Сколько времени занимает запуск?', 'Часто от нескольких дней до пары недель — зависит от объёма контента и того, базовый это запуск или кастомный визуал. Сроки фиксируем после короткого созвона.'],
        ['Нужна ли команда разработки?', 'Нет. Платформа уже собрана: настраиваем проект под ваш бренд и процесс, подключаем домен. Разработчики нужны только если вы тянете нестандартные интеграции — тогда обсуждаем отдельно.'],
        ['Можно ли начать с базового тарифа и перейти на кастомный?', 'Да. Можно выйти в бой на базовом контуре и позже усилить визуал и структуру — без смены «движка», тот же операционный слой.'],
        ['Что происходит после заявки?', 'Мы отвечаем в течение рабочего дня, уточняем задачу и предлагаем следующий шаг: демо, сценарий запуска или оценку кастома — без обязательств.'],
        ['Как работает демо?', 'По умолчанию это запрос через контакты с темой «Демо»: показываем платформу на сценарии, близком к вашему бизнесу, и разбираем, какой вариант запуска уместен.'],
        ['Что такое RentBase?', 'B2B-платформа для заявок, бронирований и управления: сайт на вашем домене, онлайн-запись и слоты, заявки, статусы и клиенты в одной системе без отдельной разработки под типовой кейс.'],
        ['Сколько стоит запуск?', 'От '.number_format($pm['pricing']['basic']['launch'] ?? 5000, 0, ',', ' ').' ₽ для базового запуска; кастомный дизайн — от '.number_format($pm['pricing']['custom']['launch'] ?? 20000, 0, ',', ' ').' ₽. Подробности на странице тарифов.'],
        ['Сколько стоит внедрение под мой бизнес?', 'Зависит от сценария. Есть базовый запуск и кастомные решения. Подскажем оптимальный вариант после заявки.'],
        ['Как развивается платформа?', 'Через модель идей: предложения клиентов, сбор интереса и взносов, затем реализация функций для участников по правилам платформы.'],
    ];
    $faqEntities = [];
    foreach ($faqs as $pair) {
        $faqEntities[] = [
            '@type' => 'Question',
            'name' => $pair[0],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $pair[1],
            ],
        ];
    }
    $graph = [
        [
            '@type' => 'FAQPage',
            'mainEntity' => $faqEntities,
        ],
    ];
@endphp

@push('jsonld')
    <x-platform.marketing.json-ld :graph="$graph" />
@endpush

@section('content')
<div class="mx-auto max-w-3xl px-3 py-10 sm:px-4 md:px-6 md:py-16">
    <h1 class="text-balance text-[clamp(1.5rem,4vw+0.75rem,2.25rem)] font-bold leading-tight text-slate-900 md:text-4xl">Частые вопросы</h1>
    <p class="mt-4 text-slate-600">Короткие ответы, чтобы снять сомнения до контакта. Нужны детали — <a href="{{ platform_marketing_contact_url() }}" class="font-medium text-blue-700 hover:text-blue-800">напишите нам</a>.</p>

    <div class="mt-10 space-y-3">
        @foreach($faqs as $i => [$q, $a])
            <details class="group rounded-xl border border-slate-200 bg-white shadow-sm open:shadow-md" data-pm-faq-index="{{ $i }}">
                <summary class="cursor-pointer list-none px-5 py-4 text-base font-semibold text-slate-900 after:float-right after:text-slate-400 after:content-['+'] open:after:content-['−'] marker:content-none [&::-webkit-details-marker]:hidden">
                    {{ $q }}
                </summary>
                <div class="border-t border-slate-100 px-5 pb-4 pt-2 text-sm leading-relaxed text-slate-600">
                    {{ $a }}
                </div>
            </details>
        @endforeach
    </div>
</div>
@endsection
