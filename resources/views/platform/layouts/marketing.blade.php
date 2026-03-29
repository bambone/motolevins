@php
    $pm = config('platform_marketing');
    $pageTitle = trim($__env->yieldContent('title')) ?: 'Главная';
    $fullTitle = $pageTitle.' — '.($pm['brand_name'] ?? 'RentBase');
    $metaDescription = trim($__env->yieldContent('meta_description', ''));
    if ($metaDescription === '') {
        $metaDescription = (string) ($pm['entity_core'] ?? '');
    }
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($metaDescription), 320, '') }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($metaDescription), 300, '') }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($metaDescription), 200, '') }}">
    @stack('meta')
    @vite(['resources/css/platform-marketing.css', 'resources/js/platform-marketing.js'])
    @stack('jsonld')
</head>
<body class="pm-body">
<header data-pm-header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3 md:px-6">
        <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight text-slate-900">{{ $pm['brand_name'] ?? 'RentBase' }}</a>
        <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium text-slate-700" aria-label="Основное меню">
            <a href="{{ url('/#dlya-kogo') }}" class="hover:text-blue-700">Для кого</a>
            <a href="{{ url('/#vozmozhnosti') }}" class="hover:text-blue-700">Возможности</a>
            <a href="{{ url('/#tarify') }}" class="hover:text-blue-700">Тарифы</a>
            <a href="{{ url('/features') }}" class="hover:text-blue-700">Подробнее</a>
            <a href="{{ url('/pricing') }}" class="hover:text-blue-700">Цены</a>
            <a href="{{ url('/faq') }}" class="hover:text-blue-700">FAQ</a>
            <a href="{{ url('/contact') }}" class="hover:text-blue-700">Контакты</a>
        </nav>
        <div class="flex w-full shrink-0 items-center gap-2 sm:w-auto">
            @if(Route::has('platform.contact'))
                <a href="{{ platform_marketing_demo_url() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-100">{{ $pm['cta']['secondary'] ?? 'Посмотреть демо' }}</a>
                <a href="{{ route('platform.contact') }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">{{ $pm['cta']['primary'] ?? 'Запустить проект' }}</a>
            @else
                <a href="{{ platform_marketing_demo_url() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-100">{{ $pm['cta']['secondary'] ?? 'Посмотреть демо' }}</a>
                <a href="{{ url('/contact') }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">{{ $pm['cta']['primary'] ?? 'Запустить проект' }}</a>
            @endif
        </div>
    </div>
</header>
<main>
    @yield('content')
</main>
<footer class="border-t border-slate-200 bg-slate-100/80">
    <div class="mx-auto max-w-6xl px-4 py-10 md:px-6">
        <div class="flex flex-col gap-6 md:flex-row md:justify-between">
            <div>
                <div class="font-semibold text-slate-900">{{ $pm['brand_name'] ?? 'RentBase' }}</div>
                <p class="mt-2 max-w-sm text-sm text-slate-600">{{ Str::limit($pm['entity_core'] ?? '', 200) }}</p>
            </div>
            <nav class="flex flex-wrap gap-4 text-sm text-slate-700" aria-label="Футер">
                <a href="{{ url('/features') }}" class="hover:text-blue-700">Возможности</a>
                <a href="{{ url('/pricing') }}" class="hover:text-blue-700">Тарифы</a>
                <a href="{{ url('/faq') }}" class="hover:text-blue-700">FAQ</a>
                <a href="{{ url('/contact') }}" class="hover:text-blue-700">Контакты</a>
                <a href="{{ url('/for-moto-rental') }}" class="hover:text-blue-700">Прокат мото</a>
                <a href="{{ url('/for-car-rental') }}" class="hover:text-blue-700">Прокат авто</a>
            </nav>
        </div>
        <p class="mt-8 text-center text-xs text-slate-500">&copy; {{ date('Y') }} {{ $pm['brand_name'] ?? 'RentBase' }}</p>
    </div>
</footer>
</body>
</html>
