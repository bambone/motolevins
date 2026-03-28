@extends('tenant.layouts.app')

@section('title', optional($motorcycle)->name ?? 'Мотоцикл')

@section('content')
    <section class="motorcycle-detail">
        {{-- Галерея, название, цена, описание, кнопка заказа --}}
        <p>Страница карточки мотоцикла</p>
    </section>
@endsection
