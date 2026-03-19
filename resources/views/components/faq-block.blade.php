<section class="py-20 lg:py-28 relative z-10 bg-[#0c0c0e] border-y border-white/[0.02]">
    <div class="max-w-3xl mx-auto px-4 md:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-3">Частые вопросы</h2>
            <p class="text-silver/80 text-lg">Всё, что нужно знать перед тем, как завести мотор.</p>
        </div>

        <div x-data="{ active: null }" class="space-y-4">
            <!-- FAQ: Можно ли уехать в другой город -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 0 ? active = 0 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Можно ли уехать в другой город?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 0 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 0" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Да. Краснодарский край и Крым — без ограничений. Выезд в другие регионы согласовывается индивидуально. Суточный лимит — 300 км, перепробег оплачивается отдельно.
                    </div>
                </div>
            </div>

            <!-- FAQ: Что если сломается -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 6 ? active = 6 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Что если сломается в дороге?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 6 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 6" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Поддержка 24/7. Если поломка по нашей вине — заменим мотоцикл или вернём деньги за неиспользованные дни. Техника проходит ТО перед каждой выдачей, поломки редки.
                    </div>
                </div>
            </div>

            <!-- FAQ: Есть ли страховка -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 7 ? active = 7 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Есть ли страховка?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 7 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 7" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        ОСАГО — на всех мотоциклах. КАСКО без франшизы — опция при бронировании. Защищает от финансовой ответственности при ДТП по чужой вине.
                    </div>
                </div>
            </div>

            <!-- FAQ 1 -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 1 ? active = 1 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Какие документы нужны для аренды?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 1" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Паспорт (возраст от 21 года) и права категории «А» (стаж от 2 лет). Только оригиналы документов.
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 2 ? active = 2 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Есть ли залог?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 2" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Да, предусмотрен возвратный депозит, размер которого зависит от класса мотоцикла (от 30 000 до 80 000 рублей). Он блокируется на карте или вносится наличными и возвращается сразу после сдачи техники без повреждений.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 3 ? active = 3 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Что со страховкой КАСКО?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 3" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Все наши мотоциклы застрахованы по ОСАГО. Для большинства моделей доступно оформление расширенного КАСКО без франшизы (опция выбирается при бронировании), что защищает вас от финансовой ответственности при ДТП по чужой вине.
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 4 ? active = 4 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Можно ли ездить на байке между городами?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 4" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Да, ограничений по маршрутам в пределах Краснодарского края и Крыма нет (выезд в другие регионы согласовывается индивидуально). Суточный лимит пробега — 300 км, перепробег оплачивается дополнительно.
                    </div>
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="bg-carbon border border-white/5 rounded-2xl overflow-hidden transition-colors hover:border-white/10">
                <button @click="active !== 5 ? active = 5 : active = null" class="w-full px-6 py-5 text-left flex justify-between items-center focus:outline-none">
                    <span class="font-bold text-white text-lg">Как проходит выдача и возврат?</span>
                    <svg class="w-5 h-5 text-moto-amber transition-transform duration-300 shrink-0 ml-4" :class="{ 'rotate-180': active === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="active === 5" x-collapse x-cloak>
                    <div class="px-6 pb-6 pt-2 text-silver text-base leading-relaxed border-t border-white/5 mt-2">
                        Мотоцикл вдается чистым и с полным баком. Процесс подписания договора и осмотра занимает 10-15 минут. Возможна доставка мотоцикла к вашему отелю или аэропорту за дополнительную плату.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
