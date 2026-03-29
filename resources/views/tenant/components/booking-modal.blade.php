<!-- Alpine Booking Modal -->
<div 
    x-data="bookingModal()"
    @open-booking-modal.window="openModal($event.detail)"
    x-show="isOpen"
    style="display: none;"
    class="relative z-50"
    aria-labelledby="modal-title" role="dialog" aria-modal="true"
>
    <!-- Overlay backdrop -->
    <div x-show="isOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] text-center sm:items-center sm:p-0 sm:pb-0">
            
            <!-- Modal Panel -->
            <div x-show="isOpen"
                 @click.away="closeModal()"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-h-[min(92dvh,calc(100svh-1rem))] transform overflow-y-auto rounded-t-2xl border border-white/10 glass-card text-left shadow-2xl shadow-moto-amber/10 transition-all sm:my-8 sm:max-h-[min(90dvh,40rem)] sm:w-full sm:max-w-lg sm:rounded-2xl">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-white/10 bg-white/5 px-4 py-4 sm:px-6 sm:py-5">
                    <h3 class="min-w-0 pr-2 text-lg font-bold leading-tight text-white sm:text-xl sm:leading-6" id="modal-title">
                        Бронирование <span class="text-moto-amber" x-text="bike.name"></span>
                    </h3>
                    <button type="button" @click="closeModal()" class="inline-flex min-h-10 min-w-10 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/10 hover:text-white touch-manipulation" aria-label="Закрыть">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body (Form) -->
                <div class="px-4 py-5 sm:px-6 sm:py-6" x-show="!isSuccess">
                    
                    <!-- Errors Alert -->
                    <div x-show="errorMessage" class="mb-6 bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm text-red-200" x-text="errorMessage"></span>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-5">
                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="booking-modal-start-date" class="block text-xs font-medium text-zinc-400 mb-1">Дата начала</label>
                                <input id="booking-modal-start-date" name="booking_start_date" type="date" x-model="form.start_date" @change="calculatePrice" required min="{{ date('Y-m-d') }}"
                                       autocomplete="off"
                                       class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder:text-zinc-500 focus:ring-1 focus:ring-moto-amber focus:border-moto-amber outline-none transition-all [color-scheme:dark]">
                            </div>
                            <div>
                                <label for="booking-modal-end-date" class="block text-xs font-medium text-zinc-400 mb-1">Дата возврата</label>
                                <input id="booking-modal-end-date" name="booking_end_date" type="date" x-model="form.end_date" @change="calculatePrice" required min="{{ date('Y-m-d') }}"
                                       autocomplete="off"
                                       class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder:text-zinc-500 focus:ring-1 focus:ring-moto-amber focus:border-moto-amber outline-none transition-all [color-scheme:dark]">
                            </div>
                        </div>

                        <!-- Price calculation display -->
                        <div x-show="calculatedDays > 0" x-collapse class="bg-white/5 rounded-xl p-4 border border-white/5">
                            <div class="flex justify-between items-center text-sm mb-1">
                                <span class="text-zinc-400">Стоимость 1 суток:</span>
                                <span class="text-white font-medium" x-text="formatMoney(bike.price) + ' ₽'"></span>
                            </div>
                            <div class="flex justify-between items-center text-sm mb-3 pb-3 border-b border-white/10">
                                <span class="text-zinc-400">Количество дней:</span>
                                <span class="text-white font-medium" x-text="calculatedDays"></span>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="text-zinc-200 font-medium">Итого к оплате:</span>
                                <span class="text-2xl font-bold text-moto-amber" x-text="formatMoney(totalPrice) + ' ₽'"></span>
                            </div>
                        </div>

                        <!-- Contacts -->
                        <div>
                            <label for="booking-modal-customer-name" class="block text-xs font-medium text-zinc-400 mb-1">Ваше имя</label>
                            <input id="booking-modal-customer-name" name="customer_name" type="text" x-model="form.customer_name" required placeholder="Иван Иванов" autocomplete="name"
                                   class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder:text-zinc-500 focus:ring-1 focus:ring-moto-amber focus:border-moto-amber outline-none transition-all">
                        </div>
                        <div>
                            <label for="booking-modal-phone" class="block text-xs font-medium text-zinc-400 mb-1">Номер телефона</label>
                            <input id="booking-modal-phone" name="phone" type="tel" x-model="form.phone" required placeholder="+7 (900) 000-00-00" autocomplete="tel"
                                   class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder:text-zinc-500 focus:ring-1 focus:ring-moto-amber focus:border-moto-amber outline-none transition-all">
                        </div>

                        <!-- Optional Comment -->
                        <div>
                            <label for="booking-modal-comment" class="block text-xs font-medium text-zinc-400 mb-1">Комментарий (необязательно)</label>
                            <textarea id="booking-modal-comment" name="customer_comment" x-model="form.customer_comment" placeholder="Например: нужна доставка в Анапу" rows="2" autocomplete="off"
                                   class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder:text-zinc-500 focus:ring-1 focus:ring-moto-amber focus:border-moto-amber outline-none transition-all resize-none"></textarea>
                        </div>

                        <!-- Submit CTA -->
                        <button type="submit" :disabled="isLoading"
                                class="tenant-btn-primary min-h-12 w-full gap-2 px-6 py-3.5 touch-manipulation disabled:opacity-50 disabled:cursor-not-allowed">
                            
                            <span x-show="!isLoading">Оставить заявку</span>
                            
                            <!-- Spinner -->
                            <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-[#0c0c0c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="isLoading">Отправка...</span>
                            
                        </button>
                    </form>
                </div>

                <!-- Success State -->
                <div class="px-4 py-10 text-center sm:px-6 sm:py-12" x-show="isSuccess" x-cloak>
                    <div class="mx-auto w-16 h-16 bg-green-500/20 text-green-400 rounded-full flex items-center justify-center mb-6 border border-green-500/30 shadow-lg shadow-green-500/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Заявка принята!</h3>
                    <p class="text-zinc-400 mb-8 max-w-sm mx-auto">
                        Наш менеджер свяжется с вами в ближайшее время для подтверждения бронирования.
                    </p>
                    <button type="button" @click="closeModal()" class="tenant-btn-secondary min-h-11 px-8 touch-manipulation">
                        Отлично, спасибо
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bookingModal', () => ({
        isOpen: false,
        isLoading: false,
        isSuccess: false,
        errorMessage: '',
        
        bike: { id: null, name: '', price: 0 },
        
        form: {
            start_date: '',
            end_date: '',
            customer_name: '',
            phone: '',
            customer_comment: '',
            source: 'site'
        },
        
        calculatedDays: 0,
        totalPrice: 0,

        formatMoney(amount) {
            return new Intl.NumberFormat('ru-RU').format(amount);
        },

        openModal(bikeData) {
            this.bike = bikeData;
            this.resetForm();
            if (bikeData.start) this.form.start_date = bikeData.start;
            if (bikeData.end) this.form.end_date = bikeData.end;
            this.calculatePrice();
            this.isOpen = true;
        },

        closeModal() {
            this.isOpen = false;
            setTimeout(() => {
                this.resetForm();
            }, 300);
        },

        resetForm() {
            this.form.start_date = '';
            this.form.end_date = '';
            this.form.customer_name = '';
            this.form.phone = '';
            this.form.customer_comment = '';
            this.calculatedDays = 0;
            this.totalPrice = 0;
            this.isSuccess = false;
            this.errorMessage = '';
        },

        calculatePrice() {
            if (!this.form.start_date || !this.form.end_date) return;
            
            const start = new Date(this.form.start_date);
            const end = new Date(this.form.end_date);
            
            if (end < start) {
                this.calculatedDays = 0;
                this.totalPrice = 0;
                return;
            }

            // Using UTC to avoid daylight saving time boundaries issues
            const MS_PER_DAY = 1000 * 60 * 60 * 24;
            const utc1 = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
            const utc2 = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
            
            this.calculatedDays = Math.floor((utc2 - utc1) / MS_PER_DAY) + 1; // Inclusive
            this.totalPrice = this.calculatedDays * this.bike.price;
        },

        async submitForm() {
            this.errorMessage = '';
            this.calculatePrice();
            
            if (this.calculatedDays <= 0) {
                this.errorMessage = 'Дата возврата не может быть раньше даты выдачи.';
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/api/leads', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        motorcycle_id: this.bike.id,
                        name: this.form.customer_name,
                        phone: this.form.phone,
                        comment: this.form.customer_comment,
                        rental_date_from: this.form.start_date,
                        rental_date_to: this.form.end_date,
                        source: 'booking_form'
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Ошибка валидации или даты уже заняты.');
                }

                // Success
                this.isSuccess = true;

            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
