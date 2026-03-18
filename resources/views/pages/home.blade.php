<x-app-layout>
    <div x-data="globalSearchState()">
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <!-- Simulated background texture -->
                <div class="absolute inset-0 bg-gradient-to-b from-black via-transparent to-black opacity-90 z-10"></div>
                <!-- If real hero image existed, it'd be here: <img src="..." class="w-full h-full object-cover opacity-40"> -->
                <div class="w-full h-full bg-[#111]"></div> 
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-white mb-6">
                    Ваш идеальный мотоцикл <br>
                    <span class="text-accent-gradient">на побережье</span>
                </h1>
                <p class="mt-4 text-xl text-gray-300 max-w-3xl mx-auto mb-10">
                    Аренда свежих мотоциклов в Геленджике, Анапе и Новороссийске. Прозрачные цены, полное обслуживание, моментальная бронь.
                </p>

                <!-- Advanced Search/Filter Bar -->
                <form @submit.prevent="applySearch" class="max-w-5xl mx-auto glass rounded-2xl p-4 flex flex-col lg:flex-row gap-4 items-end shadow-2xl relative z-20">
                    <div class="flex-1 w-full text-left">
                        <label class="block text-xs font-semibold text-gray-400 mb-1.5 ml-1 uppercase tracking-wider">Локация</label>
                        <select x-model="filters.location" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3.5 text-white text-sm focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all appearance-none cursor-pointer">
                            <option>Геленджик</option>
                            <option>Анапа</option>
                            <option>Новороссийск</option>
                        </select>
                    </div>
                    <div class="flex-1 w-full text-left">
                        <label class="block text-xs font-semibold text-gray-400 mb-1.5 ml-1 uppercase tracking-wider">Дата выдачи</label>
                        <input type="date" x-model="filters.start_date" required min="{{ date('Y-m-d') }}" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all">
                    </div>
                    <div class="flex-1 w-full text-left">
                        <label class="block text-xs font-semibold text-gray-400 mb-1.5 ml-1 uppercase tracking-wider">Дата возврата</label>
                        <input type="date" x-model="filters.end_date" required min="{{ date('Y-m-d') }}" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:ring-1 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all">
                    </div>
                    <div class="w-full lg:w-auto">
                        <button type="submit" class="w-full bg-accent-gradient hover:opacity-90 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-orange-500/25 flex items-center justify-center gap-2 h-[50px] whitespace-nowrap">
                            Найти байк
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </form>

                <!-- Trust Block -->
                <div class="mt-10 flex flex-wrap justify-center gap-4 text-gray-300 text-sm font-medium z-20 relative">
                    <div class="flex items-center gap-2 px-5 py-2.5 glass-card rounded-full"><span class="text-orange-500">✔</span> 2000+ аренд</div>
                    <div class="flex items-center gap-2 px-5 py-2.5 glass-card rounded-full"><span class="text-orange-500 text-lg">★</span> 4.9 рейтинг</div>
                    <div class="flex items-center gap-2 px-5 py-2.5 glass-card rounded-full"><span class="text-orange-500">🏆</span> 7 лет на рынке</div>
                    <div class="flex items-center gap-2 px-5 py-2.5 glass-card rounded-full"><span class="text-orange-500">🛡️</span> Полная страховка</div>
                </div>
            </div>
        </section>

        <!-- Catalog Section -->
        <section id="catalog" class="py-20 relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-end mb-12 border-b border-white/10 pb-6">
                    <div>
                        <h2 class="text-3xl font-bold text-white mb-2">Наш автопарк</h2>
                        <p class="text-gray-400">Выберите подходящий мотоцикл для вашего путешествия</p>
                    </div>
                </div>

                <!-- Bikes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($bikes as $bike)
                        <x-bike-card :bike="$bike" />
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Inject Modal Component using Alpine.js -->
        <x-booking-modal />
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('globalSearchState', () => ({
            filters: { start_date: '', end_date: '', location: 'Геленджик' },
            
            applySearch() {
                if (!this.filters.start_date || !this.filters.end_date) {
                    alert('Пожалуйста, выберите даты аренды');
                    return;
                }
                const start = new Date(this.filters.start_date);
                const end = new Date(this.filters.end_date);
                if (end < start) {
                    alert('Дата возврата не может быть раньше даты выдачи');
                    return;
                }
                document.getElementById('catalog').scrollIntoView({behavior: 'smooth'});
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('ru-RU', {day: '2-digit', month: '2-digit'});
            },

            formatPrice(amount) {
                return new Intl.NumberFormat('ru-RU').format(amount);
            },

            calculateCardTotalPrice(pricePerDay) {
                if (!this.filters.start_date || !this.filters.end_date) return 0;
                const start = new Date(this.filters.start_date);
                const end = new Date(this.filters.end_date);
                if (end < start) return 0;
                
                const MS_PER_DAY = 1000 * 60 * 60 * 24;
                const utc1 = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
                const utc2 = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
                
                const days = Math.floor((utc2 - utc1) / MS_PER_DAY) + 1;
                return days * pricePerDay;
            }
        }));
    });
    </script>
</x-app-layout>
