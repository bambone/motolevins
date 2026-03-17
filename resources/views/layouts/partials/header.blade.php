<header class="border-b">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="flex items-center justify-between py-4">
            <a href="{{ route('home') }}" class="logo">
                {{-- Логотип — слот для дизайна --}}
                <span class="font-semibold text-xl">Moto Levins</span>
            </a>

            @include('layouts.partials.nav')
        </div>
    </div>
</header>
