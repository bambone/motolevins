@php
    $videoPoster = asset('images/hero-bg.png');
    $videoSrc = asset('videos/Moto_levins_1.mp4');
@endphp
<section x-data="heroVideo()"
         x-cloak
         x-init="init()"
         @scroll.window="onScroll($event)"
         @wheel.window="onWheel($event)"
         @touchmove.window="onTouchMove($event)"
         @keydown.escape.window="onEsc()"
         id="hero-section"
         class="relative w-full min-h-[500px] sm:min-h-[560px] md:min-h-[600px] lg:min-h-[85vh] lg:min-[700px] flex items-center justify-center overflow-hidden bg-obsidian pt-[120px] md:pt-[140px] group">
    
    <!-- Video Layer (background, only when playing) -->
    <div x-show="videoPlaying"
         x-transition:enter="transition-opacity ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-400"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 z-20"
         style="display: none;">
        <video x-ref="heroVideo"
               class="absolute inset-0 w-full h-full object-cover"
               playsinline
               preload="metadata"
               poster="{{ $videoPoster }}"
               @ended="onVideoEnded"
               aria-label="POV-поездка на мотоцикле по южным дорогам">
            <source src="{{ $videoSrc }}" type="video/mp4">
        </video>
        <!-- Overlay для читаемости меню поверх видео -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-transparent to-black/60 pointer-events-none z-[25]"></div>
        
        <!-- Video Controls (внизу hero) -->
        <div class="absolute bottom-4 sm:bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 sm:gap-3 px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl bg-black/70 backdrop-blur-md border border-white/10 z-[60] pb-[max(1rem,env(safe-area-inset-bottom))]">
            <button @click="togglePlay"
                    :aria-label="isPaused ? 'Воспроизвести' : 'Пауза'"
                    class="p-2 sm:p-2.5 text-white hover:text-moto-amber rounded-lg hover:bg-white/10 focus:ring-2 focus:ring-moto-amber/50 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200">
                <svg x-show="isPaused" class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                <svg x-show="!isPaused" x-cloak class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </button>
            <button @click="toggleMute"
                    :aria-label="videoMuted ? 'Включить звук' : 'Выключить звук'"
                    class="p-2 sm:p-2.5 text-white hover:text-moto-amber rounded-lg hover:bg-white/10 focus:ring-2 focus:ring-moto-amber/50 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200">
                <svg x-show="videoMuted" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
                <svg x-show="!videoMuted" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
            </button>
            <button @click="closeVideo"
                    aria-label="Закрыть видео"
                    class="p-2 sm:p-2.5 text-white hover:text-moto-amber rounded-lg hover:bg-white/10 focus:ring-2 focus:ring-moto-amber/50 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Background Treatment (when video not playing) -->
    <div x-show="!videoPlaying" class="absolute inset-0 z-0">
        <img src="{{ asset('images/hero-bg.png') }}" alt="Motorcycle background" class="w-full h-full object-cover transition-transform duration-[20s] ease-out group-hover:scale-105" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
        <div class="w-full h-full bg-gradient-to-br from-carbon to-obsidian hidden img-fallback relative overflow-hidden">
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-3/4 h-3/4 bg-moto-amber/5 blur-[120px] rounded-full"></div>
            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 32px 32px;"></div>
        </div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-obsidian/90 to-transparent"></div>
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute bottom-0 inset-x-0 h-2/3 bg-gradient-to-t from-obsidian via-obsidian/70 to-transparent"></div>
        <!-- Spotlight for text contrast -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[140%] h-[140%] max-w-5xl rounded-full bg-radial from-black/60 via-black/20 to-transparent blur-3xl pointer-events-none"></div>
    </div>

    <!-- Hero Content (поэтапное появление: заголовок → подзаголовок → форма → микро-доверие → кнопка) -->
    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 flex flex-col items-center text-center">
        <!-- Заголовок -->
        <div class="max-w-4xl mx-auto w-full transition-[opacity,transform,filter] duration-700 ease-out opacity-100 translate-y-0 blur-0 delay-0"
             :class="videoPlaying ? '!opacity-0 -translate-y-3 blur-sm pointer-events-none delay-0' : ''">
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-[5rem] leading-[1.15] font-extrabold tracking-tight text-white mb-4 sm:mb-6 drop-shadow-[0_4px_12px_rgba(0,0,0,0.8)]">
                Прокат мотоциклов <br class="hidden sm:block">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-moto-amber to-orange-500 drop-shadow-[0_2px_8px_rgba(232,93,4,0.4)]">от 4 000 ₽/сутки</span>
            </h1>
        </div>
        <!-- Подзаголовок -->
        <div class="max-w-2xl mx-auto w-full mb-6 sm:mb-8 md:mb-10 transition-[opacity,transform,filter] duration-700 ease-out opacity-100 translate-y-0 blur-0 delay-75"
             :class="videoPlaying ? '!opacity-0 -translate-y-3 blur-sm pointer-events-none delay-0' : ''">
            <p class="text-base sm:text-lg md:text-xl text-white/85 font-medium max-w-2xl mx-auto drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)] mb-2">
                Геленджик, Анапа, Новороссийск. Экипировка включена.
            </p>
        </div>
        <!-- Форма -->
        <div class="w-full max-w-4xl transition-[opacity,transform,filter] duration-700 ease-out opacity-100 translate-y-0 blur-0 delay-150"
             :class="videoPlaying ? '!opacity-0 -translate-y-3 blur-sm pointer-events-none delay-0' : ''">
            <x-booking-bar />
        </div>
        <!-- Микро-доверие -->
        <div class="transition-[opacity,transform,filter] duration-700 ease-out opacity-100 translate-y-0 blur-0 delay-[225ms]"
             :class="videoPlaying ? '!opacity-0 -translate-y-3 blur-sm pointer-events-none delay-0' : ''">
            <x-trust-chips />
        </div>
        <!-- Кнопка Play -->
        <div class="mt-6 sm:mt-8 lg:mt-10 z-20 relative transition-[opacity,transform,filter] duration-700 ease-out opacity-100 translate-y-0 blur-0 delay-300"
             :class="videoPlaying ? '!opacity-0 -translate-y-3 blur-sm pointer-events-none delay-0' : ''">
            <button @click="playVideo"
                    type="button"
                    class="group/btn inline-flex items-center gap-2.5 sm:gap-3 px-5 sm:px-6 py-3 sm:py-3.5 bg-white/5 hover:bg-white/10 border border-white/20 hover:border-moto-amber/50 text-white font-medium rounded-xl transition-all duration-300 backdrop-blur-sm active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-moto-amber/50 focus:ring-offset-2 focus:ring-offset-obsidian"
                    :aria-label="videoEnded ? 'Посмотреть видео ещё раз' : 'Смотреть, как это ощущается'">
                <span class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-moto-amber/20 group-hover/btn:bg-moto-amber/30 transition-colors">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-moto-amber ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </span>
                <span x-text="videoEnded ? 'Посмотреть ещё раз' : 'Смотреть, как это ощущается'" class="text-sm sm:text-base"></span>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        if (window.heroVideoRegistered) return;
        window.heroVideoRegistered = true;
        
        Alpine.data('heroVideo', () => ({
            videoPlaying: false,
            videoMuted: false,
            videoEnded: false,
            isPaused: false,
            reducedMotion: false,
            heroVisibleRatio: 1,
            
            init() {
                this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden && this.videoPlaying) this.closeVideo();
                });
                const hero = document.getElementById('hero-section');
                if (hero) {
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            this.heroVisibleRatio = entry.intersectionRatio;
                            if (!this.videoPlaying) return;
                            if (entry.intersectionRatio < 0.5) {
                                this.closeVideo();
                                this.resetToPoster();
                            } else if (entry.intersectionRatio < 0.65) {
                                this.closeVideo();
                            }
                        });
                    }, { threshold: [0, 0.25, 0.5, 0.65, 0.75, 1] });
                    io.observe(hero);
                }
            },
            
            playVideo() {
                const video = this.$refs.heroVideo;
                if (!video) return;
                this.videoEnded = false;
                this.videoPlaying = true;
                window.dispatchEvent(new CustomEvent('hero-video-playing'));
                this.isPaused = false;
                this.$nextTick(() => {
                    video.muted = this.videoMuted;
                    video.currentTime = 0;
                    video.play().catch(() => {});
                });
            },
            
            closeVideo() {
                this.pauseVideo();
                this.videoPlaying = false;
                window.dispatchEvent(new CustomEvent('hero-video-stopped'));
            },
            
            pauseVideo() {
                const video = this.$refs.heroVideo;
                if (video) video.pause();
                this.isPaused = true;
            },
            
            onVideoEnded() {
                this.videoPlaying = false;
                window.dispatchEvent(new CustomEvent('hero-video-stopped'));
                this.videoEnded = true;
                this.isPaused = true;
            },
            
            resetToPoster() {
                const video = this.$refs.heroVideo;
                if (video) video.currentTime = 0;
            },
            
            onEsc() {
                if (this.videoPlaying) this.closeVideo();
            },
            
            onScroll() {
                if (!this.videoPlaying) return;
                this.closeVideo();
            },
            
            onWheel() {
                if (!this.videoPlaying) return;
                this.closeVideo();
            },
            
            onTouchMove() {
                if (!this.videoPlaying) return;
                this.closeVideo();
            }
        }));
    });
    </script>
</section>
