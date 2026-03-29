@php
    $casesContactUrl = platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch');
@endphp
<section id="primery" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-16 sm:py-24" aria-labelledby="primery-heading">
    <div class="relative z-10 mx-auto max-w-6xl px-3 sm:px-4 md:px-6">
        <div class="text-center">
            @if(!empty($pm['cases_intro']))
                <p class="mb-4 text-center text-sm text-slate-500">{{ $pm['cases_intro'] }}</p>
            @endif
            <h2 id="primery-heading" class="fade-reveal text-balance text-2xl font-bold leading-tight text-slate-900 sm:text-3xl md:text-4xl">Примеры проектов</h2>
            <p class="fade-reveal mx-auto mt-3 max-w-2xl text-sm font-medium text-slate-700 sm:text-base" style="transition-delay: 80ms;">Первые проекты уже работают на платформе</p>
            <p class="fade-reveal mx-auto mt-3 max-w-2xl text-base leading-relaxed text-slate-600" style="transition-delay: 100ms;">Только реальные сайты или честные плейсхолдеры — без вымышленных брендов.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 md:mt-16 lg:grid-cols-3">
            @foreach($pm['cases'] ?? [] as $index => $case)
                <article class="group fade-reveal pm-reveal-cases-{{ min($index, 4) }} flex cursor-default flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-transform duration-300 hover:-translate-y-1.5 hover:shadow-md sm:p-6">

                    @if($index === 0)
                        <!-- MotoLevins Mockup View (Realistic Hero Mini) -->
                        <div class="relative flex aspect-video w-full flex-col items-center justify-center overflow-hidden rounded-xl border border-slate-200 p-4 shadow-inner transition-colors group-hover:border-slate-300">
                            <!-- Background: Simulated Sunset Road -->
                            <div class="absolute inset-0 z-0 bg-gradient-to-br from-slate-900 via-slate-800 to-amber-900/60"></div>
                            <div class="absolute inset-0 z-0 bg-[linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.7))]"></div>

                            <!-- Browser Header Overlay (Minimal) -->
                            <div class="absolute inset-x-0 top-0 z-10 flex h-4 items-center px-2 opacity-70">
                                <span class="mr-0.5 h-1 w-1 rounded-full bg-slate-400"></span>
                                <span class="mr-0.5 h-1 w-1 rounded-full bg-slate-400"></span>
                                <span class="h-1 w-1 rounded-full bg-slate-400"></span>
                            </div>

                            <!-- Hero Content -->
                            <div class="relative z-10 mt-2 flex w-full flex-col items-center">
                                <!-- Headline -->
                                <div class="mb-1.5 h-2.5 w-3/4 rounded-full bg-white opacity-90 shadow-sm"></div>
                                <div class="mb-3 h-2.5 w-1/2 rounded-full bg-white opacity-90 shadow-sm"></div>

                                <!-- Amber Price Line -->
                                <div class="mb-3 h-3 w-1/3 rounded-full shadow-sm" style="background-color: var(--color-moto-amber, #e85d04);"></div>

                                <!-- Small Subtitle -->
                                <div class="mb-1 h-1 w-2/5 rounded-full bg-white/60"></div>
                                <div class="mb-4 h-1 w-1/4 rounded-full bg-white/60"></div>

                                <!-- Booking Bar -->
                                <div class="flex w-full max-w-[85%] gap-1 rounded-lg border border-white/10 bg-black/60 p-1.5 shadow-lg backdrop-blur-sm transition-transform duration-500 group-hover:-translate-y-0.5">
                                    <div class="box-border flex flex-1 flex-col justify-center rounded border-r border-white/5 bg-white/5 py-1 pl-1.5">
                                       <div class="mb-0.5 h-0.5 w-1/3 rounded bg-white/30"></div>
                                       <div class="h-1 w-1/2 rounded bg-white/60"></div>
                                    </div>
                                    <div class="box-border flex flex-1 flex-col justify-center rounded border-r border-white/5 bg-white/5 py-1 pl-1.5">
                                       <div class="mb-0.5 h-0.5 w-1/3 rounded bg-white/30"></div>
                                       <div class="h-1 w-1/2 rounded bg-white/60"></div>
                                    </div>
                                    <div class="flex flex-1 flex-col justify-center rounded bg-white/5 py-1 pl-1.5">
                                       <div class="mb-0.5 h-0.5 w-1/3 rounded bg-white/30"></div>
                                       <div class="h-1 w-1/2 rounded bg-white/60"></div>
                                    </div>

                                    <!-- CTA Button -->
                                    <div class="flex w-10 items-center justify-center rounded p-0.5" style="background-color: var(--color-moto-amber, #e85d04);">
                                        <div class="h-1 w-3/4 rounded-sm bg-black/80"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Skeleton Empty State view -->
                        <div class="relative flex aspect-video w-full flex-col items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50 shadow-inner transition-colors group-hover:bg-slate-100">
                            <div class="h-12 w-12 rounded-full border-[3px] border-dashed border-slate-200 opacity-50 animate-[spin_10s_linear_infinite]"></div>
                            <div class="mt-4 h-2 w-20 rounded-full bg-slate-200 opacity-70"></div>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <h3 class="font-bold text-slate-900 transition-colors group-hover:text-pm-accent">{{ $case['title'] }}</h3>
                        @if(!empty($case['real']))
                            <span class="text-xs font-medium text-green-600">Реальный проект</span>
                        @endif
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $case['type'] }}</p>
                    @if(!empty($case['real']) && $index === 0)
                        <p class="mt-2 text-xs font-medium text-slate-500">Публичный сайт и приём заявок в бою.</p>
                    @endif

                    @if(!empty($case['url']) && !empty($case['real']))
                        <a href="{{ $case['url'] }}" class="mt-auto mt-5 inline-flex items-center gap-1 text-sm font-bold text-pm-accent transition-colors hover:text-pm-accent-hover" rel="noopener noreferrer" target="_blank" data-pm-event="case_open" data-pm-case="{{ e($case['title'] ?? '') }}">
                            Открыть сайт
                            <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <span class="mt-auto mt-5 inline-block self-start rounded-lg bg-slate-100 px-3 py-1 text-sm font-medium text-slate-500">Ожидается</span>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="fade-reveal mt-12 flex flex-col items-center gap-4 border-t border-slate-200 pt-12 text-center sm:mt-16 sm:pt-16" style="transition-delay: 400ms;">
            <p class="max-w-xl text-base font-semibold text-slate-800">Ваш проект может быть следующим</p>
            <p class="max-w-xl text-base leading-relaxed text-slate-600">Готовы к такому же публичному сайту и контуру заявок — с рабочей системой, а не картинками в портфолио.</p>
            <a href="{{ $casesContactUrl }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-pm-accent px-8 py-3 text-base font-bold text-white shadow-premium transition-all hover:-translate-y-0.5 hover:bg-pm-accent-hover" data-pm-event="cta_click" data-pm-cta="primary" data-pm-location="cases_footer">
                Запустить свой проект
            </a>
        </div>
    </div>
</section>
