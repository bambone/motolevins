@extends('tenant.layouts.app')

@php
    use App\Support\Typography\RussianTypography;

    $brand = trim((string) ($site_name ?? ''));
    if ($brand === '') {
        $brand = 'Компания';
    }
@endphp

@section('title', 'О нас')

@section('content')
    <section class="pt-24 pb-8 sm:pt-28 sm:pb-10">
        <div class="mx-auto max-w-6xl px-3 sm:px-4 md:px-8">
            <h1 class="text-balance text-2xl font-bold leading-tight text-white sm:text-3xl md:text-4xl">{{ RussianTypography::tiePrepositionsToNextWord((string) (($resolvedSeo ?? null)?->h1 ?? 'О компании')) }}</h1>
        </div>
    </section>

    <section class="pb-12 sm:pb-16">
        <div class="mx-auto max-w-3xl space-y-6 px-3 text-sm leading-relaxed text-silver sm:space-y-8 sm:px-4 sm:text-base md:px-8">
            <p class="text-white/90">
                <strong class="text-white">{{ $brand }}</strong> — краткое представление бренда и формата работы. Точные условия, прайс и детали сервиса публикуются в разделах сайта и согласуются с вами при заявке.
            </p>
            <p>
                Актуальные материалы — на <a href="{{ route('home') }}" class="font-semibold text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">главной</a>
                и в <a href="{{ route('contacts') }}" class="font-semibold text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">контактах</a>;
                ответы на частые вопросы — в <a href="{{ route('faq') }}" class="font-semibold text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">FAQ</a>.
            </p>
            <p class="text-silver/90">
                Нужна отдельная посадочная с текстом «под бренд» — опубликуйте страницу «О нас» в конструкторе (адрес /about) или задайте тему с собственным шаблоном.
            </p>
        </div>
    </section>
@endsection
