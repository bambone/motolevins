@props(['bike'])

<div class="glass-card rounded-2xl overflow-hidden flex flex-col group relative transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(255,107,0,0.15)] hover:border-orange-500/30 cursor-pointer"
     @click="$dispatch('open-booking-modal', { id: {{ $bike->id }}, name: '{{ $bike->name }}', price: {{ $bike->price_per_day }}, start: filters.start_date, end: filters.end_date })">
    <!-- Image -->
    <div class="relative h-64 bg-[#1a1a1a] overflow-hidden border-b border-white/5 shrink-0">
        @if($bike->image)
            <img src="/images/{{ $bike->image }}" alt="{{ $bike->name }}" class="block w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
        @endif
        <div class="absolute inset-0 flex items-center justify-center text-gray-600 text-sm img-fallback {{ $bike->image ? 'hidden' : '' }}">
            [Фото {{ $bike->name }}]
        </div>
        
        <!-- Badge -->
        <div class="absolute top-4 right-4 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10 z-10">
            <span class="text-xs font-semibold text-orange-400 uppercase tracking-wider">{{ $bike->type }}</span>
        </div>
        
        <!-- Hover Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-1 relative z-10">
        <div class="flex justify-between items-start mb-2">
            <div>
                <h3 class="text-xl font-bold text-white mb-1 leading-tight group-hover:text-orange-400 transition-colors">{{ $bike->name }}</h3>
                <p class="text-sm text-gray-400 flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    {{ $bike->engine }} cc
                </p>
            </div>
        </div>

        <!-- Default Price -->
        <div class="mt-auto pt-4 flex items-center justify-between" x-show="!filters.start_date || !filters.end_date">
            <div>
                <span class="text-xs text-gray-400 uppercase tracking-wider block mb-0.5">От</span>
                <span class="text-2xl font-bold text-white">{{ number_format($bike->price_per_day, 0, ',', ' ') }} ₽<span class="text-sm text-gray-400 font-normal">/сутки</span></span>
            </div>
        </div>

        <!-- Inline Dates Killer Feature (Alpine dynamic) -->
        <div class="mt-4 p-3.5 bg-white/5 rounded-xl border border-white/10 transition-all transform origin-bottom" x-show="filters.start_date && filters.end_date" x-cloak>
            <div class="flex flex-col text-sm border-b border-orange-500/20 pb-2 mb-2">
                <span class="text-gray-400 flex items-center gap-1.5"><svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <span x-text="formatDate(filters.start_date)"></span> &nbsp;&rarr;&nbsp; <span x-text="formatDate(filters.end_date)"></span></span>
            </div>
            <div class="flex justify-between items-end">
                <span class="text-white font-bold text-xl"><span x-text="formatPrice(calculateCardTotalPrice({{ $bike->price_per_day }}))"></span> ₽</span>
                <span class="text-xs text-gray-400 uppercase">ЗА ВЕСЬ ПЕРИОД</span>
            </div>
        </div>

        <!-- Big CTA Button -->
        <button class="w-full mt-5 bg-white/10 text-white group-hover:bg-accent-gradient font-bold py-3.5 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2 border border-white/10 group-hover:border-transparent group-hover:shadow-orange-500/25">
            Забронировать
            <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </button>
    </div>
</div>
