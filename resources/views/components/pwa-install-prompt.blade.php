<div x-data="pwaInstallPrompt()"
     x-init="initPrompt()"
     x-show="showPrompt"
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="opacity-0 translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-full"
     class="fixed bottom-4 inset-x-4 md:bottom-8 md:right-8 md:left-auto md:w-96 z-[60] bg-carbon/90 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-5"
     style="display: none;">
    
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-moto-amber to-orange-700 flex items-center justify-center shrink-0 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-white font-bold text-base mb-1">Установить Moto Levins</h4>
            <p class="text-silver/90 text-sm leading-snug mb-4">Добавьте на главный экран для быстрого доступа к каталогу в 1 клик.</p>
            <div class="flex gap-3">
                <button @click="installApp()" class="flex-1 bg-moto-amber hover:bg-orange-600 text-white font-bold text-sm py-2 px-4 rounded-lg transition-colors shadow-lg shadow-moto-amber/20">Установить</button>
                <button @click="dismissPrompt()" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white text-sm font-medium rounded-lg transition-colors border border-white/10">Позже</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pwaInstallPrompt', () => ({
        deferredPrompt: null,
        showPrompt: false,
        
        initPrompt() {
            // Prevent showing if already installed/standalone
            if (window.matchMedia('(display-mode: standalone)').matches) return;

            // Check suppression policy (7 days max age)
            const dismissedAt = localStorage.getItem('pwa_prompt_dismissed');
            if (dismissedAt) {
                const daysPassed = (Date.now() - parseInt(dismissedAt)) / (1000 * 60 * 60 * 24);
                if (daysPassed < 7) return; 
            }

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                
                // Contextual trigger 1: Time
                setTimeout(() => {
                    if (this.deferredPrompt) this.showPrompt = true;
                }, 15000);

                // Contextual trigger 2: Meaningful scroll
                const scrollHandler = () => {
                    if (window.scrollY > window.innerHeight * 0.5) {
                        this.showPrompt = true;
                        window.removeEventListener('scroll', scrollHandler);
                    }
                };
                window.addEventListener('scroll', scrollHandler, { passive: true });
            });
            
            // Listen for successful install cleanly
            window.addEventListener('appinstalled', () => {
                this.showPrompt = false;
                this.deferredPrompt = null;
                console.log('Moto Levins PWA safely installed');
            });
        },

        async installApp() {
            if (!this.deferredPrompt) return;
            
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('PWA installation accepted');
            } else {
                this.dismissPrompt();
            }
            
            this.deferredPrompt = null;
            this.showPrompt = false;
        },

        dismissPrompt() {
            this.showPrompt = false;
            localStorage.setItem('pwa_prompt_dismissed', Date.now().toString());
        }
    }));
});
</script>
