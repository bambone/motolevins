@extends('platform.layouts.marketing')

@section('title', 'Контакты')

@section('meta_description')
Свяжитесь с RentBase: запуск проекта, демо платформы или обсуждение кастомного внедрения. Короткая форма, ответ в течение рабочего дня.
@endsection

@php
    $pm = config('platform_marketing');
    $cp = $pm['contact_page'] ?? [];
    $intentConfig = is_array($cp['intents'] ?? null) ? $cp['intents'] : [];
    $intentKeys = array_keys($intentConfig);
    $launch = (string) ($pm['intent']['launch'] ?? 'launch');
    $rawIntent = request('intent');
    $activeIntent = is_string($rawIntent) && in_array($rawIntent, $intentKeys, true)
        ? $rawIntent
        : null;
    $meta = ($activeIntent !== null && isset($intentConfig[$activeIntent])) ? $intentConfig[$activeIntent] : [];
    $pageTitle = (string) ($meta['title'] ?? ($cp['default_title'] ?? 'Контакты'));
    $pageLead = (string) ($meta['lead'] ?? ($cp['default_lead'] ?? ''));
    $base = request()->getSchemeAndHttpHost();
    $graph = [
        [
            '@type' => 'ContactPage',
            'name' => $pageTitle.' — '.($pm['brand_name'] ?? 'RentBase'),
            'url' => $base.'/contact',
            'description' => strip_tags($pageLead),
        ],
        [
            '@type' => 'Organization',
            'name' => $pm['organization']['name'] ?? 'RentBase',
            'url' => $base,
        ],
    ];
    $email = config('mail.from.address', 'hello@rentbase.su');
    $sent = session('platform_contact_sent');
    $formAction = Route::has('platform.contact.store') ? route('platform.contact.store') : url('/contact');
@endphp

@push('jsonld')
    <x-platform.marketing.json-ld :graph="$graph" />
@endpush

@section('content')
<div class="mx-auto max-w-3xl px-3 py-10 sm:px-4 md:px-6 md:py-16">
    @if($sent)
        <div class="rounded-2xl border border-green-200 bg-green-50 p-6 sm:p-8" role="status" data-pm-contact-success="1" data-pm-contact-intent="{{ e(session('platform_contact_intent', '')) }}">
            <h1 class="text-balance text-2xl font-bold text-slate-900 md:text-3xl">{{ $cp['success_title'] ?? 'Заявка отправлена' }}</h1>
            <p class="mt-3 text-base text-slate-700">{{ $cp['success_lead'] ?? '' }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ $cp['success_next'] ?? '' }}</p>
            <p class="mt-6 text-sm text-slate-600">
                Email: <a href="mailto:{{ $email }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $email }}</a>
            </p>
            <p class="mt-6 text-sm text-slate-500">{{ $pm['cta']['pricing_reassurance'] ?? 'Ответим в течение дня' }}</p>
            <a href="{{ url('/') }}" class="mt-8 inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">На главную</a>
        </div>
    @else
        <h1 class="text-balance text-[clamp(1.5rem,4vw+0.75rem,2.25rem)] font-bold leading-tight text-slate-900 md:text-4xl">{{ $pageTitle }}</h1>
        <p class="mt-4 text-lg text-slate-600">{{ $pageLead }}</p>

        @if($activeIntent === (string) ($pm['intent']['demo'] ?? 'demo'))
            <div class="mb-6 mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <p class="mb-2 text-sm font-medium text-slate-900">{{ $cp['demo_outline_title'] ?? 'Что вы увидите на демо:' }}</p>
                <ul class="space-y-1 text-sm text-slate-600">
                    @foreach($cp['demo_outline'] ?? [] as $pt)
                        <li>• {{ $pt }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($pm['cta']['demo_expectation']) && $activeIntent === (string) ($pm['intent']['demo'] ?? 'demo'))
            <p class="mt-3 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ $pm['cta']['demo_expectation'] }}</p>
        @endif

        <ul class="mt-6 flex flex-col gap-2 text-sm text-slate-600 sm:flex-row sm:flex-wrap">
            @foreach($cp['expectation_bullets'] ?? [] as $bullet)
                <li class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-pm-accent" aria-hidden="true"></span>
                    {{ $bullet }}
                </li>
            @endforeach
        </ul>

        @php
            $trustContact = $pm['trust_micro']['contact'] ?? [];
        @endphp
        @if(!empty($trustContact) && is_array($trustContact))
            <ul class="mt-4 space-y-1 text-xs text-slate-500">
                @foreach(array_slice($trustContact, 0, 3) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="mb-2 text-sm font-medium text-slate-900">{{ $cp['after_apply_title'] ?? 'Что будет после заявки:' }}</p>
            <ul class="space-y-1 text-sm text-slate-600">
                @foreach($cp['after_apply_steps'] ?? [] as $step)
                    <li>• {{ $step }}</li>
                @endforeach
            </ul>
        </div>

        <form method="post"
              action="{{ $formAction }}"
              class="mt-10 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
              data-pm-contact-form="1"
              novalidate>
            @csrf
            <input type="hidden" name="company_site" value="" autocomplete="off" tabindex="-1" class="absolute -left-[9999px] h-px w-px opacity-0" aria-hidden="true">

            @foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utmKey)
                @if(request()->filled($utmKey))
                    <input type="hidden" name="{{ $utmKey }}" value="{{ request($utmKey) }}">
                @endif
            @endforeach

            <div>
                <label for="pm-contact-intent" class="block text-sm font-medium text-slate-800">Тема обращения</label>
                <select id="pm-contact-intent" name="intent" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-pm-accent focus:outline-none focus:ring-1 focus:ring-pm-accent">
                    @foreach($intentConfig as $key => $row)
                        <option value="{{ $key }}" @selected(old('intent', $activeIntent ?? $launch) === $key)>{{ is_array($row) ? ($row['title'] ?? $key) : $key }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="pm-contact-name" class="block text-sm font-medium text-slate-800">Имя <span class="text-red-600">*</span></label>
                <input id="pm-contact-name" name="name" type="text" required maxlength="255" autocomplete="name" value="{{ old('name') }}"
                       class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-pm-accent focus:outline-none focus:ring-1 focus:ring-pm-accent @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="pm-contact-phone" class="block text-sm font-medium text-slate-800">Телефон</label>
                    <input id="pm-contact-phone" name="phone" type="tel" maxlength="40" autocomplete="tel" value="{{ old('phone') }}"
                           class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-pm-accent focus:outline-none focus:ring-1 focus:ring-pm-accent @error('phone') border-red-400 @enderror">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pm-contact-email" class="block text-sm font-medium text-slate-800">Email</label>
                    <input id="pm-contact-email" name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email') }}"
                           class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-pm-accent focus:outline-none focus:ring-1 focus:ring-pm-accent @error('email') border-red-400 @enderror">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="-mt-2 text-xs text-slate-500">{{ $cp['form_help'] ?? 'Укажите телефон или email.' }}</p>

            <div>
                <label for="pm-contact-message" class="block text-sm font-medium text-slate-800">Ниша и задача <span class="text-red-600">*</span></label>
                <textarea id="pm-contact-message" name="message" required rows="5" minlength="15" maxlength="2000"
                          class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-pm-accent focus:outline-none focus:ring-1 focus:ring-pm-accent @error('message') border-red-400 @enderror"
                          placeholder="Например: прокат мото в Сочи, нужны онлайн-бронирования и учёт парка">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            @error('company_site')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-pm-accent px-6 py-3 text-base font-bold text-white shadow-premium transition-colors hover:bg-pm-accent-hover sm:w-auto"
                    data-pm-event="contact_submit"
                    data-pm-location="contact_page">
                Отправить
            </button>
            <p class="text-center text-xs text-slate-500 sm:text-left">{{ $pm['cta']['pricing_reassurance'] ?? 'Ответим в течение дня' }}</p>
        </form>

        <div class="mt-10 rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Напрямую по email</h2>
            <a href="mailto:{{ $email }}" class="mt-2 inline-block text-lg font-medium text-blue-700 hover:text-blue-800">{{ $email }}</a>
            <p class="mt-4 text-sm text-slate-600">Если удобнее сразу описать задачу письмом — укажите нишу, город и желаемые сроки.</p>
        </div>
    @endif
</div>
@endsection
