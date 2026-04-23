@extends('platform.layouts.marketing')

@section('title', 'Возможности')

@section('meta_description')
RentBase: мультитенантный сайт на своём домене, конструктор страниц и темы оформления, каталог услуг и техники, онлайн-запись и слоты, бронирование, CRM и заявки, отзывы и FAQ, SEO и редиректы, админка для команды, уведомления и интеграции.
@endsection

@php
    $pm = app(\App\Product\Settings\MarketingContentResolver::class)->resolved();
    $base = platform_marketing_canonical_origin() ?: request()->getSchemeAndHttpHost();
    $graph = [
        [
            '@type' => 'WebPage',
            'name' => 'Возможности — '.($pm['brand_name'] ?? 'RentBase'),
            'url' => $base.'/features',
            'description' => 'Полный обзор модулей RentBase: публичный сайт, запись и бронирование, CRM, маркетинг, SEO, админка тенанта и уведомления.',
        ],
        [
            '@type' => 'Organization',
            'name' => $pm['organization']['name'] ?? 'RentBase',
            'url' => $base,
        ],
    ];
@endphp

@push('jsonld')
    <x-platform.marketing.json-ld :graph="$graph" />
@endpush

@section('content')
@php
    use App\Support\Typography\RussianTypography;
@endphp
<div class="pm-marketing-features mx-auto max-w-6xl px-3 pb-12 sm:px-4 sm:pb-16 md:px-6 md:pb-20">
    <header class="max-w-3xl">
        <h1 class="scroll-mt-28 text-balance text-[clamp(1.5rem,4vw+0.75rem,2.25rem)] font-bold leading-tight text-slate-900 md:text-4xl">Возможности платформы</h1>
        <p class="mt-4 text-pretty text-lg leading-relaxed text-slate-600">{{ RussianTypography::tiePrepositionsToNextWord((string) ($pm['entity_core'] ?? '')) }}</p>
        <p class="mt-4 text-pretty text-base leading-relaxed text-slate-600">
            {{ RussianTypography::tiePrepositionsToNextWord('Ниже — не маркетинговый лозунг, а сквозная карта продуктовых модулей: от публичного сайта до CRM, расписания, SEO и уведомлений. Всё работает в одной базе и одной админке тенанта — без «склейки» сторонних сервисов.') }}
        </p>
    </header>

    <div class="mt-10 grid gap-5 sm:mt-12 sm:grid-cols-2 sm:gap-6">
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Что такое RentBase в одном предложении?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Это операционная платформа для сервисного бизнеса: публичный сайт с конструктором страниц, каталог предложений (услуги или техника — по нише), онлайн-запись и бронирование, единая воронка заявок и клиентов, плюс админка для команды и сценарии уведомлений.') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Для каких ниш подходит продукт?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Прокат мото- и автотехники, детейлинг и автосервисы по записи, курсы вождения и инструкторы, мастер-классы, студии и мастерские с календарём — везде, где важны слоты, заявки, статусы и история контакта без ручного хаоса в мессенджерах.') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Чем это отличается от «просто сайта»?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Контент страниц связан с расписанием, заявками, каталогом и базой лидов: посетитель записывается или оставляет заявку, команда видит очередь, статусы и контекст в одном месте — без выгрузок и таблиц «на стороне».') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Есть ли отдельный кабинет для бизнеса?')">
            <p class="text-pretty">{!! str_replace('Filament', '<span class="font-medium text-slate-800">Filament</span>', RussianTypography::tiePrepositionsToNextWord('Да. У каждого клиентского проекта (тенанта) — своя панель на базе Filament: страницы и секции, настройки сайта и бренда, каталог, расписание, заявки и CRM, отзывы, SEO, согласия, команда и сценарии уведомлений.')) !!}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Можно ли свой домен и бренд?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Да: подключается собственный домен, в админке настраиваются логотип, цвет, фавикон и публичные медиа. Публичные страницы отдаются в контексте вашего бренда, а не «конструктора в общем виде».') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Как устроена запись и бронирование?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Есть настраиваемые услуги с длительностью слота, шагом сетки, буферами и горизонтом бронирования; расчёт доступных слотов; сценарии мгновенного подтверждения и «по согласованию». Каталог может быть связан с техникой (прокат) или только с услугами — в зависимости от темы и бизнес-модели.') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Что с заявками и клиентами?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Лиды и CRM-заявки с публичных форм, единая карточка контакта, типы заявок, UTM, каналы связи (телефон, мессенджеры), история — чтобы менеджеру не прыгать между почтой и таблицами.') }}</p>
        </x-platform.marketing.answer-block>
        <x-platform.marketing.answer-block :question="RussianTypography::tiePrepositionsToNextWord('Есть ли SEO и сопровождение трафика?')">
            <p class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Метаданные и заголовки по страницам, карта сайта, редиректы, human-readable URL, поддержка JSON-LD и материалов для поиска; на стороне платформы — маркетинговые страницы с документацией ниш (прокат, авто, сервисы).') }}</p>
        </x-platform.marketing.answer-block>
    </div>

    <div class="mx-auto mt-14 max-w-4xl space-y-6 sm:mt-16 sm:space-y-8">
        <section aria-labelledby="feat-site" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-site" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Публичный сайт и контент') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Конструктор страниц — секции, порядок, предпросмотр; темы оформления под разные ниши (в т.ч. витрины с «техническим» и экспертным контентом).'), 'Конструктор страниц') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Медиа и бренд — логотип, hero, галереи и файлы в хранилище тенанта; PWA-манифест и визуальные акценты.'), 'Медиа и бренд') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Типовые блоки — герой, ленты, формы заявок, отзывы, FAQ, подвал: гибрид готовых секций и вашего текста.'), 'Типовые блоки') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-catalog" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-catalog" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Каталог, программы и предложения') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Каталог услуг и/или единиц техники (зависит от темы: прокат, сервис, обучение).'), 'услуг и/или единиц техники') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Программы и пакеты — описание, обложки, CTA, связка с публичными формами.'), 'Программы и пакеты') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Связанные сущности — например, услуга записи, привязанная к объекту флота или к направлению детейлинга.'), 'Связанные сущности') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-sched" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-sched" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Расписание, слоты и бронирование') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Настройка длительности, шага сетки, буферов до/после, уведомлений о брони и горизонта планирования.'), 'длительности, шага сетки, буферов') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Расчёт доступности слотов с учётом нагрузки и правил; сценарии «сразу подтверждено» и «ждёт оператора».'), 'доступности слотов') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Интеграция с календарями и целями расписания (цели смен, ресурсы) — для сложного сервиса, а не только «красивая сетка».'), 'календарями и целями') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-crm" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-crm" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Заявки, CRM и коммуникации') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Публичные формы (заявка, консультация, запись) с валидацией, UTM, контекстом страницы и согласиями — где это требуется.'), 'Публичные формы') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('CRM-заявки — тип, статус, полезная нагрузка (в т.ч. сценарии для ниш вроде детейлинга или обучения).'), 'CRM-заявки') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Каналы — единообразное использование телефона, мессенджеров и почты в подвале и формах.'), 'Каналы') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-trust" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-trust" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Доверие: отзывы, FAQ, юридические сценарии') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Модерация отзывов, публичные и скрытые статусы, витрина на сайте.'), 'отзывов') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('FAQ с привязкой к странице или общий пул вопросов.'), 'FAQ') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Согласия при брони и заявках (включая сценарии с обязательной политикой) — в одном фреймворке, без сторонних виджетов.'), 'Согласия') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-seo" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 id="feat-seo" class="text-pretty text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Маркетинг, SEO и инфраструктура тенанта') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('SEO — мета по страницам, карты сайта, редиректы, аналитические настройки.'), 'SEO') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Подвал сайта — либо автоматический минимальный (контакты и ссылки), либо настраиваемые секции в админке.'), 'Подвал сайта') !!}</li>
                <li class="text-pretty">{!! RussianTypography::wrapPhrase(RussianTypography::tiePrepositionsToNextWord('Надёжность — квоты и хранилище медиа, изоляция данных по тенантам, сценарии пуш-уведомлений и почтовой рассылки по правилам.'), 'Надёжность') !!}</li>
            </ul>
        </section>

        <section aria-labelledby="feat-summary" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-slate-50/90 p-5 sm:p-6">
            <h2 id="feat-summary" class="text-pretty text-lg font-bold text-slate-900 sm:text-xl">{{ RussianTypography::tiePrepositionsToNextWord('Коротко: что получает владелец бизнеса') }}</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600 marker:text-slate-400 sm:text-base">
                <li class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Один контур: сайт → запись/бронь → заявка → команда — без зоопарка интеграций.') }}</li>
                <li class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Гибкость ниш: от проката с календарём техники до детейлинга и курсов с формами и CRM.') }}</li>
                <li class="text-pretty">{{ RussianTypography::tiePrepositionsToNextWord('Прозрачность для сотрудников: заявки, статусы, напоминания, история в одной панели.') }}</li>
            </ul>
        </section>
    </div>

    <p class="mt-12 text-pretty text-sm text-slate-500">
        {{ RussianTypography::tiePrepositionsToNextWord('Актуальный перечень опций в тарифах — на странице') }}
        <a href="{{ url('/pricing') }}" class="font-medium text-blue-700 hover:text-blue-800">«Тарифы»</a>.
        {{ RussianTypography::tiePrepositionsToNextWord('Подобрать сценарий под вашу нишу — в блоках') }}
        <a href="{{ url('/for-moto-rental') }}" class="font-medium text-blue-700 hover:text-blue-800">прокат мото</a>,
        <a href="{{ url('/for-car-rental') }}" class="font-medium text-blue-700 hover:text-blue-800">прокат авто</a>,
        <a href="{{ url('/for-services') }}" class="font-medium text-blue-700 hover:text-blue-800">сервисы по записи</a>.
    </p>

    <p class="mt-6">
        <a href="{{ url('/pricing') }}" class="font-medium text-blue-700 hover:text-blue-800">Тарифы</a>
        <span class="mx-2 text-slate-300">·</span>
        <a href="{{ platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch') }}" class="font-medium text-blue-700 hover:text-blue-800">Запустить проект</a>
    </p>
</div>
@endsection
