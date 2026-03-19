<header x-data="headerScroll()"
        x-init="init()"
        @hero-video-playing.window="videoPlaying = true"
        @hero-video-stopped.window="videoPlaying = false"
        :class="[
            videoPlaying ? 'bg-obsidian/90 backdrop-blur-xl border-b border-white/10 shadow-xl' : (compact ? 'bg-obsidian/85 backdrop-blur-xl border-b border-white/[0.08] shadow-xl' : 'bg-gradient-to-b from-black/60 to-transparent'),
            reducedMotion ? 'transition-none' : 'transition-[background-color,backdrop-filter,border-color,box-shadow] duration-500 ease-out'
        ]"
        class="fixed top-0 w-full z-50 flex items-center">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 flex justify-between items-center transition-[height,padding] duration-500 ease-out"
         :class="compact ? 'h-16 md:h-20 py-2' : 'h-20 md:h-28 py-4'">
        <!-- Logo: умеренно крупнее вверху, компактнее при скролле (разница ~30-40%) -->
        <div class="flex-shrink-0 flex items-center h-full">
            <a href="{{ route('home') }}" class="flex items-center gap-3 active:scale-[0.98] transition-transform duration-300">
                <div class="relative flex items-center justify-center transition-[width,height] duration-500 ease-out shrink-0"
                     :class="compact ? 'w-10 h-10 md:w-12 md:h-12' : 'w-14 h-14 md:w-16 md:h-16'">
                    <img src="{{ asset('images/logo-round-dark.png') }}" alt="Moto Levins"
                         width="64" height="64"
                         class="absolute inset-0 w-full h-full object-contain rounded-full" />
                </div>
                <span class="font-bold tracking-tight text-white transition-[font-size] duration-500 ease-out leading-none"
                      :class="compact ? 'text-lg md:text-xl' : 'text-xl md:text-2xl'">Moto Levins</span>
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
                <svg class="w-4 h-4 text-moto-amber shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                <span class="hidden lg:inline">+7 (913) 060-86-89</span>
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        if (window.headerScrollRegistered) return;
        window.headerScrollRegistered = true;
        Alpine.data('headerScroll', () => ({
            scrollY: 0,
            compact: false,
            videoPlaying: false,
            reducedMotion: false,
            init() {
                this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const update = () => {
                    this.scrollY = window.pageYOffset || document.documentElement.scrollTop;
                    this.compact = this.scrollY > 60;
                };
                update();
                window.addEventListener('scroll', update, { passive: true });
            }
        }));
    });
    </script>
</header>
