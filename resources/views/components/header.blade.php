<header x-data="{ scrolled: false }" 
        @scroll.window="scrolled = (window.pageYOffset > 50)" 
        :class="scrolled ? 'bg-obsidian/70 backdrop-blur-2xl border-b border-white/10 shadow-2xl' : 'bg-gradient-to-b from-black/60 to-transparent'"
        class="fixed top-0 w-full z-50 transition-all duration-300 h-16 lg:h-20 flex items-center">
    <div class="w-full max-w-7xl mx-auto px-4 md:px-8 flex justify-between items-center">
        <!-- Logo -->
        <div class="flex-shrink-0 flex items-center gap-2">
            <a href="{{ route('home') }}" class="flex items-center gap-3 active:scale-[0.98] transition-transform">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-moto-amber to-orange-700 flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">Moto Levins</span>
            </a>
        </div>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex flex-1 justify-center space-x-8">
            <a href="#catalog" class="text-sm font-medium text-white hover:text-moto-amber transition-colors">Автопарк</a>
            <a href="#" class="text-sm font-medium text-silver hover:text-white transition-colors">Правила аренды</a>
            <a href="#" class="text-sm font-medium text-silver hover:text-white transition-colors">Контакты</a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-4">
            <a href="tel:+79130608689" class="hidden sm:flex items-center gap-2 text-sm font-medium text-silver hover:text-white transition-colors active:scale-[0.98]">
                <svg class="w-4 h-4 text-moto-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                +7 (913) 060-86-89
            </a>
        </div>
    </div>
</header>
