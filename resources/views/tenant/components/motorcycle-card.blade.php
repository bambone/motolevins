@props(['name' => 'Название', 'price' => '—', 'url' => '#'])

{{-- Карточка мотоцикла для каталога и превью на главной --}}
<article {{ $attributes->merge(['class' => 'motorcycle-card']) }}>
    <div class="motorcycle-card__image"></div>
    <h3 class="motorcycle-card__name">{{ $name }}</h3>
    <p class="motorcycle-card__price">{{ $price }} р./сутки</p>
    <a href="{{ $url }}">Подробнее</a>
</article>
