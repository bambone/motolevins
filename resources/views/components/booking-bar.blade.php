<form @submit.prevent="applySearch" class="w-full max-w-4xl bg-white/10 backdrop-blur-2xl border border-white/20 rounded-2xl p-4 lg:p-5 flex flex-col lg:flex-row gap-4 items-end shadow-2xl shadow-black/50 relative z-20 transition-all duration-300">
    
    <div class="flex-1 w-full text-left">
        <label for="location" class="block text-xs font-semibold text-silver/80 mb-2 ml-1 uppercase tracking-wider">Локация</label>
        <div class="relative group">
            <select id="location" x-model="filters.location" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3.5 text-white/90 focus:bg-black/80 focus:ring-2 focus:ring-moto-amber/50 focus:border-moto-amber/30 outline-none transition-all appearance-none cursor-pointer hover:border-white/30 h-14 shadow-inner">
                <option value="">Выберите город</option>
                <option>Геленджик</option>
                <option>Анапа</option>
                <option>Новороссийск</option>
            </select>
            <!-- Dropdown Icon -->
            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-white/50 group-hover:text-white/80 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </div>
    </div>
    
    <div class="flex-1 w-full text-left relative">
        <label for="start_date" class="block text-xs font-semibold text-silver/80 mb-2 ml-1 uppercase tracking-wider">Дата выдачи</label>
        <input type="date" id="start_date" x-model="filters.start_date" required min="{{ date('Y-m-d') }}" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3.5 text-white/90 focus:bg-black/80 focus:ring-2 focus:ring-moto-amber/50 focus:border-moto-amber/30 outline-none transition-all hover:border-white/30 h-14 shadow-inner [color-scheme:dark]">
    </div>
    
    <div class="flex-1 w-full text-left relative">
        <label for="end_date" class="block text-xs font-semibold text-silver/80 mb-2 ml-1 uppercase tracking-wider">Дата возврата</label>
        <input type="date" id="end_date" x-model="filters.end_date" required min="{{ date('Y-m-d') }}" class="w-full bg-black/60 border border-white/10 rounded-xl px-4 py-3.5 text-white/90 focus:bg-black/80 focus:ring-2 focus:ring-moto-amber/50 focus:border-moto-amber/30 outline-none transition-all hover:border-white/30 h-14 shadow-inner [color-scheme:dark]">
    </div>
    
    <div class="w-full lg:w-auto mt-2 lg:mt-0">
        <!-- Main Focal CTA -->
        <button type="submit" 
                :disabled="isSearching"
                :class="isSearching ? 'opacity-75 cursor-not-allowed' : 'active:scale-[0.98] hover:bg-[#ff6a00] hover:shadow-moto-amber/40 hover:-translate-y-0.5'"
                class="w-full bg-moto-amber text-white px-8 lg:px-10 py-3.5 rounded-xl font-bold transition-all flex items-center justify-center gap-3 h-14 whitespace-nowrap shadow-xl shadow-moto-amber/20 border border-transparent">
            <template x-if="!isSearching">
                <div class="flex items-center gap-2 text-[15px] tracking-wide">
                    Найти байк
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>
            </template>
            <template x-if="isSearching">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Поиск...
                </div>
            </template>
        </button>
    </div>
</form>
