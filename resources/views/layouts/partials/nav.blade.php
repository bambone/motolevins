<nav aria-label="Основная навигация">
    <ul class="flex flex-wrap items-center gap-4 md:gap-6">
        <li><a href="{{ route('home') }}" class="hover:underline">Главная</a></li>
        <li><a href="{{ route('motorcycles.index') }}">Мотоциклы</a></li>
        <li><a href="{{ route('prices') }}">Цены</a></li>
        <li><a href="{{ route('order') }}">Заказать</a></li>
        <li class="relative group">
            <button type="button" aria-expanded="false" aria-haspopup="true">Дополнительно</button>
            <ul class="absolute left-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity" role="menu">
                <li><a href="{{ route('reviews') }}" role="menuitem">Отзывы</a></li>
                <li><a href="{{ route('terms') }}" role="menuitem">Условия</a></li>
                <li><a href="{{ route('faq') }}" role="menuitem">FAQ</a></li>
                <li><a href="{{ route('about') }}" role="menuitem">О нас</a></li>
                <li><a href="{{ route('articles.index') }}" role="menuitem">Статьи</a></li>
            </ul>
        </li>
        <li class="relative group">
            <button type="button" aria-expanded="false" aria-haspopup="true">Доставка</button>
            <ul class="absolute left-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity" role="menu">
                <li><a href="{{ route('delivery.anapa') }}" role="menuitem">Анапа</a></li>
                <li><a href="{{ route('delivery.gelendzhik') }}" role="menuitem">Геленджик</a></li>
            </ul>
        </li>
        <li><a href="{{ route('contacts') }}">Контакты</a></li>
    </ul>
</nav>
