@extends('layouts.app')

@section('title', 'Мотоциклы')

@section('content')
    <section class="page-header py-12 container mx-auto px-4 max-w-6xl">
        <h1 class="text-3xl font-bold">Каталог мотоциклов</h1>
    </section>

    {{-- Фильтры: категория (Спорт, Круизер, Эндуро) --}}
    <section class="filters container mx-auto px-4 max-w-6xl">
    </section>

    {{-- Сетка карточек мотоциклов --}}
    <section class="motorcycles-grid py-12 container mx-auto px-4 max-w-6xl">
        {{-- @each('components.motorcycle-card', $motorcycles, 'motorcycle') --}}
        <p>Слот для карточек мотоциклов</p>
    </section>
@endsection
