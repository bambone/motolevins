@props(['section' => null])
<section class="relative z-10 overflow-x-clip bg-obsidian py-16 sm:py-20 lg:py-28">
    <div class="mx-auto max-w-7xl px-3 sm:px-4 md:px-8">
        <div class="mx-auto mb-10 max-w-2xl text-center sm:mb-14">
            <h2 class="mb-3 text-balance text-2xl font-bold leading-tight text-white sm:text-3xl md:text-4xl">Как это работает</h2>
            <p class="text-sm leading-relaxed text-zinc-300 sm:text-base md:text-lg">Весь процесс занимает не более 15 минут. Четыре шага — и вы в пути.</p>
        </div>

        {{-- Карточки шагов: номер → иконка → текст по вертикали, без absolute-наслоения --}}
        <div class="relative z-10 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4 lg:gap-6">
            <!-- Step 1 -->
            <div class="group flex flex-col rounded-2xl border border-white/[0.08] bg-white/[0.02] p-5 transition-[border-color,background-color] duration-300 hover:border-white/15 hover:bg-white/[0.035] sm:p-6">
                <span class="mb-4 select-none text-4xl font-black tabular-nums leading-none text-moto-amber/25 transition-colors duration-300 group-hover:text-moto-amber/40 sm:text-5xl sm:mb-5">01</span>
                <div class="mb-5 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-carbon shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h3 class="mb-2 text-lg font-bold leading-snug text-white">Выберите байк</h3>
                <p class="text-sm leading-relaxed text-zinc-300">Модель + даты. Всё.</p>
            </div>

            <!-- Step 2 -->
            <div class="group flex flex-col rounded-2xl border border-white/[0.08] bg-white/[0.02] p-5 transition-[border-color,background-color] duration-300 hover:border-white/15 hover:bg-white/[0.035] sm:p-6">
                <span class="mb-4 select-none text-4xl font-black tabular-nums leading-none text-moto-amber/25 transition-colors duration-300 group-hover:text-moto-amber/40 sm:text-5xl sm:mb-5">02</span>
                <div class="mb-5 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-carbon shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </div>
                <h3 class="mb-2 text-lg font-bold leading-snug text-white">Оставьте заявку</h3>
                <p class="text-sm leading-relaxed text-zinc-300">Имя, телефон, даты — 2 минуты.</p>
            </div>

            <!-- Step 3 -->
            <div class="group flex flex-col rounded-2xl border border-white/[0.08] bg-white/[0.02] p-5 transition-[border-color,background-color] duration-300 hover:border-white/15 hover:bg-white/[0.035] sm:p-6">
                <span class="mb-4 select-none text-4xl font-black tabular-nums leading-none text-moto-amber/25 transition-colors duration-300 group-hover:text-moto-amber/40 sm:text-5xl sm:mb-5">03</span>
                <div class="mb-5 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-carbon shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="mb-2 text-lg font-bold leading-snug text-white">Бронь подтверждена</h3>
                <p class="text-sm leading-relaxed text-zinc-300">Менеджер свяжется в течение 10 минут.</p>
            </div>

            <!-- Step 4 -->
            <div class="group flex flex-col rounded-2xl border border-moto-amber/20 bg-moto-amber/[0.06] p-5 transition-[border-color,background-color] duration-300 hover:border-moto-amber/35 hover:bg-moto-amber/[0.09] sm:p-6">
                <span class="mb-4 select-none text-4xl font-black tabular-nums leading-none text-moto-amber/35 transition-colors duration-300 group-hover:text-moto-amber/50 sm:text-5xl sm:mb-5">04</span>
                <div class="mb-5 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-transparent bg-moto-amber shadow-lg shadow-moto-amber/25">
                    <svg class="h-6 w-6 text-[#0c0c0c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>
                <h3 class="mb-2 text-lg font-bold leading-snug text-white">Ключ на старт</h3>
                <p class="text-sm leading-relaxed text-zinc-300">Чистый байк, полный бак — в путь.</p>
            </div>
        </div>
    </div>
</section>
