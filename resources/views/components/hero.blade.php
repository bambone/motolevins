<section class="relative w-full min-h-[500px] lg:min-h-[85vh] lg:min-[700px] flex items-center justify-center overflow-hidden bg-obsidian pt-16 group">
    <!-- Background Treatment -->
    <div class="absolute inset-0 z-0">
        <!-- Base image with fallback -->
        <img src="/images/hero-bg.png" alt="Motorcycle background" class="w-full h-full object-cover transition-transform duration-[20s] ease-out group-hover:scale-105" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
        
        <!-- Premium Fallback Texture if image fails -->
        <div class="w-full h-full bg-gradient-to-br from-carbon to-obsidian hidden img-fallback relative overflow-hidden">
            <!-- Subtle glow orb for atmosphere -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-3/4 h-3/4 bg-moto-amber/5 blur-[120px] rounded-full"></div>
            <!-- Grid noise texture simulation -->
            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 32px 32px;"></div>
        </div>
        
        <!-- Multi-layered Overlay System -->
        <!-- 1. Top readability gradient for header -->
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-obsidian/90 to-transparent"></div>
        
        <!-- 2. Center soft darkening for typography punch -->
        <div class="absolute inset-0 bg-black/20"></div>
        
        <!-- 3. Bottom heavy integration gradient starting from 50% -->
        <div class="absolute bottom-0 inset-x-0 h-2/3 bg-gradient-to-t from-obsidian via-obsidian/70 to-transparent"></div>
    </div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 md:px-8 mt-12 md:mt-0 flex flex-col items-center text-center">
        <!-- Headlines -->
        <div class="max-w-4xl mx-auto mb-12">
            <!-- Added drop shadow to text for premium pop against any background -->
            <h1 class="text-5xl md:text-6xl lg:text-[5rem] leading-[1.1] font-extrabold tracking-tight text-white mb-6 drop-shadow-2xl">
                Испытайте дух <br class="hidden sm:block">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-moto-amber to-orange-500">настоящей свободы</span>
            </h1>
            <p class="text-lg md:text-xl text-silver/90 font-medium max-w-2xl mx-auto drop-shadow-md">
                Элитарный прокат мотоциклов на Черноморском побережье. Безупречный сервис, свежая техника и километры идеальных дорог.
            </p>
        </div>

        <!-- Booking Bar (Focal Point) -->
        <x-booking-bar />

        <!-- Trust Chips -->
        <x-trust-chips />
    </div>
</section>
