@php
    $urlBasic = platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch');
    $urlCustom = platform_marketing_contact_url($pm['intent']['custom'] ?? 'custom');
    $p = $pm['pricing'] ?? [];
    $reassurance = $pm['cta']['pricing_reassurance'] ?? 'Ответим в течение дня';
    $supportLine = $pm['cta']['pricing_support_line'] ?? '';
    $planHelp = $p['plan_help'] ?? '';
    $underPrice = $p['under_price'] ?? ['Без команды разработки', 'Без дополнительных интеграторов'];
    $pricingTrustMicro = array_slice($pm['trust_micro']['pricing'] ?? [], 0, 3);
@endphp
<section id="tarify" class="pm-section-anchor border-b border-slate-200 bg-slate-50 py-16 sm:py-24" aria-labelledby="tarify-heading">
    <div class="relative z-10 mx-auto max-w-6xl px-3 sm:px-4 md:px-6">
        <h2 id="tarify-heading" class="fade-reveal text-balance text-center text-2xl font-bold leading-tight text-slate-900 sm:text-3xl md:text-4xl">Прозрачные тарифы</h2>
        <p class="fade-reveal mx-auto mt-3 max-w-2xl text-center text-base font-medium text-slate-800" style="transition-delay: 80ms;">{!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $p['intro'] ?? 'Прозрачная модель без скрытых затрат') !!}</p>
        <p class="fade-reveal mx-auto mt-2 max-w-2xl text-pretty text-center text-base leading-relaxed text-slate-600" style="transition-delay: 100ms;">{!! str_replace([' для ', ' с ', ' в ', ' и ', ' — '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;', '&nbsp;— '], $p['sub_intro'] ?? 'Выбирайте формат под ваш бизнес. Переход между тарифами — в любой момент.') !!}</p>
        @if($planHelp !== '')
            <p class="fade-reveal mx-auto mt-3 max-w-2xl text-center text-sm text-slate-600" style="transition-delay: 120ms;">{!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $planHelp) !!}</p>
        @endif

        <div class="mx-auto mt-16 grid max-w-4xl gap-8 lg:grid-cols-2 lg:gap-12">

            <!-- Standard Plan -->
            <div class="fade-reveal relative flex cursor-default flex-col rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-transform duration-300 hover:-translate-y-1.5 hover:shadow-md" style="transition-delay: 200ms;">
                <h3 class="text-xl font-bold text-slate-900">{{ $p['basic']['name'] ?? 'Бизнес' }}</h3>
                <p class="mt-2 text-sm text-slate-600">Все необходимые инструменты для&nbsp;работы.</p>

                <div class="mt-6 flex flex-col gap-2">
                    <div class="text-[min(2.5rem,8vw)] font-extrabold leading-none tracking-tight text-slate-900">
                        {{ number_format($p['basic']['launch'] ?? 0, 0, ',', ' ') }} ₽ <span class="text-xl font-medium tracking-normal text-slate-400">запуск</span>
                    </div>
                    <div class="inline-flex max-w-fit items-center gap-1.5 rounded-md bg-slate-50 px-2.5 py-1 text-sm font-medium text-slate-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        {{ number_format($p['basic']['monthly'] ?? 0, 0, ',', ' ') }} ₽ / месяц
                    </div>
                </div>
                @if(!empty($underPrice) && is_array($underPrice))
                    <ul class="mt-3 space-y-1 text-xs text-slate-500">
                        @foreach($underPrice as $line)
                            <li>{!! str_replace([' для ', ' с ', ' в '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;'], $line) !!}</li>
                        @endforeach
                    </ul>
                @endif

                <ul class="mt-8 flex-1 space-y-3 text-sm text-slate-600">
                    @foreach($p['basic']['bullets'] ?? [] as $b)
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $b) !!}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ $urlBasic }}" class="mt-8 block w-full rounded-xl bg-slate-900 py-3 text-center text-sm font-bold tracking-wide text-white transition-colors hover:bg-slate-800" data-pm-event="cta_click" data-pm-cta="primary" data-pm-location="pricing_basic" data-pm-tier="basic">Подключить</a>
                <p class="mt-2 text-center text-xs text-slate-500">{{ $reassurance }}</p>
                @if($supportLine !== '')
                    <p class="mt-1 text-center text-xs text-slate-500">{{ $supportLine }}</p>
                @endif
                @if(!empty($pricingTrustMicro))
                    <ul class="mt-3 space-y-1 text-center text-xs text-slate-500">
                        @foreach($pricingTrustMicro as $line)
                            <li>{!! str_replace([' для ', ' с ', ' в '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;'], $line) !!}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Custom / Enterprise Plan -->
            <div class="fade-reveal relative flex cursor-default flex-col overflow-hidden rounded-3xl border border-white/10 bg-navy p-8 shadow-xl transition-transform duration-300 hover:-translate-y-1.5 hover:shadow-2xl" style="transition-delay: 350ms;">
                <!-- Glowing accent bg -->
                <div class="pointer-events-none absolute right-0 top-0 h-64 w-64 translate-x-1/2 -translate-y-1/2 animate-glow-breath rounded-full bg-pm-accent opacity-30 blur-[60px]"></div>

                <div class="relative z-10 flex h-full flex-col">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">{{ $p['custom']['name'] ?? 'Кастомный' }}</h3>
                        <span class="inline-flex rounded-full bg-pm-accent/20 px-2.5 py-0.5 text-xs font-semibold text-blue-300 ring-1 ring-inset ring-pm-accent/30">Популярный</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-300">Для сложных процессов и&nbsp;больших парков.</p>

                    <div class="mt-6 flex flex-col gap-2">
                        <div class="text-[min(2.5rem,8vw)] font-extrabold leading-none tracking-tight text-white">
                            {{ number_format($p['custom']['launch'] ?? 0, 0, ',', ' ') }} ₽ <span class="text-xl font-medium tracking-normal text-slate-400">запуск</span>
                        </div>
                        <div class="inline-flex max-w-fit items-center gap-1.5 rounded-md bg-white/5 px-2.5 py-1 text-sm font-medium text-slate-300">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-pm-accent"></span>
                            {{ number_format($p['custom']['monthly'] ?? 0, 0, ',', ' ') }} ₽ / месяц
                        </div>
                    </div>
                    @if(!empty($underPrice) && is_array($underPrice))
                        <ul class="mt-3 space-y-1 text-xs text-slate-400">
                            @foreach($underPrice as $line)
                                <li>{!! str_replace([' для ', ' с ', ' в '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;'], $line) !!}</li>
                            @endforeach
                        </ul>
                    @endif

                    <ul class="mt-8 flex-1 space-y-3 text-sm text-slate-300">
                        @foreach($p['custom']['bullets'] ?? [] as $b)
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $b) !!}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ $urlCustom }}" class="mt-8 block w-full rounded-xl bg-pm-accent py-3 text-center text-sm font-bold tracking-wide text-white shadow-premium transition-colors hover:bg-pm-accent-hover" data-pm-event="cta_click" data-pm-cta="consult" data-pm-location="pricing_custom" data-pm-tier="custom">Обсудить проект</a>
                    <p class="mt-2 text-center text-xs text-slate-400">{{ $reassurance }}</p>
                    @if($supportLine !== '')
                        <p class="mt-1 text-center text-xs text-slate-400">{{ $supportLine }}</p>
                    @endif
                </div>
            </div>

        </div>

        @if(!empty($p['footer_choice']))
            <p class="mt-6 text-center text-sm text-slate-500">{!! str_replace([' для ', ' с ', ' в '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;'], $p['footer_choice']) !!}</p>
        @endif
    </div>
</section>
