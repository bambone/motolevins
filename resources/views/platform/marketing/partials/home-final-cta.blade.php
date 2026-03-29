@php
    $finalHeadline = $pm['cta']['final_headline'] ?? 'Готовы навести порядок в бронированиях?';
    $finalSubtitle = $pm['cta']['final_subtitle'] ?? 'Запустим ваш проект уже в ближайшие дни';
    $finalTrust = $pm['cta']['final_trust'] ?? 'Без риска • Без сложной разработки';
    $finalTrustMicro = array_slice($pm['trust_micro']['final'] ?? [], 0, 3);
    $urlLaunch = platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch');
    $urlDemo = platform_marketing_demo_url();
@endphp
<section class="pm-section-anchor relative overflow-hidden bg-white py-16 sm:py-24 sm:py-32" aria-labelledby="final-cta-heading">
    <!-- Fine Dot Grid Background -->
    <div class="fade-reveal absolute inset-0 z-0 opacity-40" style="transition-delay: 500ms; background-image: radial-gradient(#e2e8f0 1.5px, transparent 1.5px); background-size: 32px 32px;"></div>

    <!-- Ambient Glow -->
    <div class="pointer-events-none absolute bottom-0 left-1/2 h-[600px] w-[600px] -translate-x-1/2 translate-y-1/2 animate-glow-breath rounded-full bg-pm-accent/10 blur-[100px]"></div>

    <div class="relative z-10 mx-auto max-w-4xl px-3 text-center sm:px-4 md:px-6">
        <h2 id="final-cta-heading" class="fade-reveal text-balance text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl md:text-5xl">{!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $finalHeadline) !!}</h2>
        <p class="fade-reveal mx-auto mt-6 max-w-2xl text-base font-medium text-slate-800 sm:text-lg" style="transition-delay: 120ms;">
            {!! str_replace([' для ', ' с ', ' в ', ' и '], [' для&nbsp;', ' с&nbsp;', ' в&nbsp;', ' и&nbsp;'], $finalSubtitle) !!}
        </p>
        <p class="fade-reveal mx-auto mt-3 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg" style="transition-delay: 150ms;">
            Оставьте заявку&nbsp;— соберём демо под&nbsp;ваш бизнес и&nbsp;покажем, как&nbsp;это работает на&nbsp;ваших сценариях.
        </p>

        <div class="fade-reveal mt-10 flex flex-col justify-center gap-4 sm:flex-row" style="transition-delay: 300ms;">
            <a href="{{ $urlLaunch }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-pm-accent px-8 py-3 text-base font-bold text-white shadow-premium transition-all hover:-translate-y-0.5 hover:bg-pm-accent-hover" data-pm-event="cta_click" data-pm-cta="primary" data-pm-location="final">
                Оставить заявку
            </a>
            <a href="{{ $urlDemo }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-8 py-3 text-base font-semibold text-slate-700 transition-colors hover:bg-slate-50" data-pm-event="cta_click" data-pm-cta="secondary" data-pm-location="final">
                Посмотреть демо парка
            </a>
        </div>
        <p class="fade-reveal mt-6 text-sm font-medium text-slate-600" style="transition-delay: 450ms;">{{ $finalTrust }}</p>
        @if(!empty($finalTrustMicro))
            <ul class="fade-reveal mx-auto mt-3 flex max-w-xl flex-col gap-1 text-left text-xs text-slate-500 sm:text-center" style="transition-delay: 480ms;">
                @foreach($finalTrustMicro as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif
        <p class="fade-reveal mt-2 text-sm text-slate-400" style="transition-delay: 500ms;">{{ $pm['cta']['pricing_reassurance'] ?? 'Ответим в течение дня' }}</p>
    </div>
</section>
