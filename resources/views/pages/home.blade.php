@extends('layouts.app')

@section('title', 'Главная')

@section('content')
    {{-- Hero: Прокат мотоциклов на побережье --}}
    <section id="hero" class="hero py-16 md:py-24 container mx-auto px-4 max-w-6xl">
        <h1 class="text-3xl md:text-4xl font-bold">Прокат мотоциклов на побережье</h1>
        <p class="mt-4 text-lg">тел: 8 (913) 060-86-89</p>
    </section>

    {{-- Быстрая аренда: форма заявки --}}
    <section id="quick-order" class="quick-order py-12 container mx-auto px-4 max-w-6xl">
        <h2>Быстрая аренда</h2>
        {{-- Форма: категория (Спорт/Круизер/Эндуро), даты, контакты --}}
    </section>

    {{-- О прокате --}}
    <section id="about-preview" class="about-preview py-12 container mx-auto px-4 max-w-6xl">
        <h2 class="text-2xl font-semibold">О прокате</h2>
    </section>

    {{-- Преимущества --}}
    <section id="advantages" class="advantages py-12 container mx-auto px-4 max-w-6xl">
        <h2 class="text-2xl font-semibold">Преимущества</h2>
    </section>

    {{-- Каталог мотоциклов (превью) --}}
    <section id="motorcycles-preview" class="motorcycles-preview py-12 container mx-auto px-4 max-w-6xl">
        <h2 class="text-2xl font-semibold">Широкий выбор мотоциклов в прокат</h2>
        <a href="{{ route('motorcycles.index') }}" class="inline-block mt-4 underline">Все мотоциклы</a>
    </section>

    {{-- География: Новороссийск, Анапа, Геленджик, Ростов --}}
    <section id="geography" class="geography py-12 container mx-auto px-4 max-w-6xl">
        <h2 class="text-2xl font-semibold">Аренда в городах побережья</h2>
    </section>

    {{-- Миссия --}}
    <section id="mission" class="mission py-12 container mx-auto px-4 max-w-6xl">
        <h2 class="text-2xl font-semibold">Наша миссия</h2>
    </section>
@endsection
