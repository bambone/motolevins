@extends('tenant.layouts.app')

@section('content')
    <div class="mx-auto flex min-h-[50vh] max-w-lg flex-col items-center justify-center px-4 pb-24 pt-16 text-center sm:pt-24">
        <p class="text-[0.7rem] font-bold uppercase tracking-[0.2em] text-moto-amber">404</p>
        <h1 class="mt-3 text-balance text-2xl font-bold leading-tight tracking-tight sm:text-3xl {{ tenant()?->themeKey() === 'advocate_editorial' ? 'text-[rgb(24_27_32)]' : 'text-white' }}">
            Страница не найдена
        </h1>
        <p class="mt-4 max-w-md text-pretty text-[15px] leading-relaxed {{ tenant()?->themeKey() === 'advocate_editorial' ? 'text-[rgb(65_72_82)]' : 'text-silver/90' }}">
            Такой страницы нет или ссылка устарела. Проверьте адрес или вернитесь на главную.
        </p>
        <a href="{{ route('home') }}"
           class="tenant-btn-primary mt-8 inline-flex min-h-12 items-center justify-center rounded-xl px-8 text-[15px] font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">
            На главную
        </a>
    </div>
@endsection
