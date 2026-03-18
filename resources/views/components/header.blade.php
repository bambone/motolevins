<header class="fixed top-0 w-full z-50 glass border-b border-white/5 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center gap-2">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-accent-gradient flex items-center justify-center shadow-lg shadow-orange-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">Moto <span class="text-gray-400">Levins</span></span>
                </a>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex flex-1 justify-center space-x-8">
                <a href="#" class="text-sm font-medium text-white hover:text-orange-400 transition-colors">Аренда</a>
                <a href="#" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Правила</a>
                <a href="#" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Контакты</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-4">
                <a href="tel:+79130608689" class="hidden sm:flex items-center gap-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    +7 (913) 060-86-89
                </a>
            </div>
        </div>
    </div>
</header>
