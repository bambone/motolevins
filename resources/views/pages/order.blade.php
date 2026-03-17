@extends('layouts.app')

@section('title', 'Заказать')

@section('content')
    <section class="page-header py-12 container mx-auto px-4 max-w-6xl">
        <h1 class="text-3xl font-bold">Заявка на аренду</h1>
    </section>

    {{-- Форма заказа: мотоцикл, даты, контакты, сообщение --}}
    <section class="order-form py-12 container mx-auto px-4 max-w-6xl">
        <form action="#" method="POST">
            @csrf
            <p>Слот для формы заказа</p>
        </form>
    </section>
@endsection
