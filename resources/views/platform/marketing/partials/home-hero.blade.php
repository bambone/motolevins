@php
    $heroVariant = $pm['hero_variant'] ?? 'c';
    $heroHeadline = $pm['hero'][$heroVariant] ?? ($pm['hero']['c'] ?? '');
    $heroSubline = trim((string) ($pm['hero_cta_subline'] ?? ''));
    $heroProofFallback = trim((string) ($pm['hero_cta_proof'] ?? ''));
    $heroNext = $pm['hero_next_step'] ?? '';
    $trustBiz = $pm['trust']['businesses'] ?? '';
    $heroTrustMicro = array_slice($pm['trust_micro']['hero'] ?? [], 0, 3);
    $urlLaunch = platform_marketing_contact_url($pm['intent']['launch'] ?? 'launch');
    $urlDemo = platform_marketing_demo_url();
@endphp
<section id="hero" class="pm-section-anchor relative overflow-hidden border-b border-slate-200 bg-slate-50" aria-labelledby="hero-heading">
    <div class="relative z-10 mx-auto max-w-6xl px-3 pb-16 pt-8 sm:px-4 sm:pb-24 sm:pt-12 md:px-6 md:pb-32 md:pt-16">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-8">

            <div class="max-w-2xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-pm-accent/20 bg-pm-accent/5 px-3 py-1 text-sm font-semibold text-pm-accent fade-reveal" style="transition-delay: 50ms;">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-pm-accent" aria-hidden="true"></span>
                    {{ $pm['hero_badge'] ?? 'Бронирования и заявки в одном контуре' }}
                </div>
                <h1 id="hero-heading" class="fade-reveal text-balance text-4xl font-extrabold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl md:text-6xl" style="transition-delay: 150ms;">
                    {{ $heroHeadline }}
                </h1>
                <p class="fade-reveal mt-6 text-lg leading-relaxed text-slate-600 sm:text-xl md:mt-8" style="transition-delay: 250ms;">
                    {{ $pm['hero_subtitle'] ?? '' }}
                </p>
                <div class="fade-reveal mt-8 flex flex-col gap-4 sm:flex-row md:mt-10" style="transition-delay: 350ms;">
                    <a href="{{ $urlLaunch }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-pm-accent px-8 py-3 text-base font-bold text-white shadow-lg transition-colors hover:bg-pm-accent-hover" data-pm-event="cta_click" data-pm-cta="primary" data-pm-location="hero">
                        {{ $pm['cta']['primary'] }}
                    </a>
                    <a href="{{ $urlDemo }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-8 py-3 text-base font-semibold text-slate-700 transition-colors hover:bg-slate-50" data-pm-event="cta_click" data-pm-cta="secondary" data-pm-location="hero">
                        {{ $pm['cta']['secondary'] }}
                    </a>
                </div>
                @if($heroSubline !== '')
                    <p class="fade-reveal mt-4 text-sm text-slate-500" style="transition-delay: 400ms;">{{ $heroSubline }}</p>
                @elseif($heroProofFallback !== '')
                    <p class="fade-reveal mt-4 text-sm font-medium text-slate-600" style="transition-delay: 400ms;">{{ $heroProofFallback }}</p>
                @endif
                <p class="fade-reveal mt-2 text-xs text-slate-400" style="transition-delay: 420ms;">Уже используют {{ $trustBiz }} бизнесов</p>
                @if($heroNext !== '')
                    <p class="fade-reveal mt-2 text-sm text-slate-500" style="transition-delay: 450ms;">{{ $heroNext }}</p>
                @endif
                @if(!empty($heroTrustMicro))
                    <ul class="fade-reveal mt-4 flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:flex-wrap sm:gap-x-4" style="transition-delay: 480ms;">
                        @foreach($heroTrustMicro as $line)
                            <li class="flex items-center gap-1.5"><span class="h-1 w-1 shrink-0 rounded-full bg-pm-accent" aria-hidden="true"></span>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Один макет + одна карточка, без blur/glow и тяжёлых анимаций (LCP / main thread) --}}
            <div class="pm-hero-mockup fade-reveal relative ml-auto hidden w-full max-w-[540px] lg:block xl:max-w-none" style="transition-delay: 200ms;">
                <div class="flex h-[400px] w-full max-w-[540px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl" style="width:100%;max-width:540px;height:400px;">
                    <div class="flex h-12 flex-none items-center justify-between border-b border-slate-100 bg-slate-50 px-4">
                        <div class="flex gap-1.5" aria-hidden="true">
                            <div class="h-3 w-3 rounded-full bg-slate-300"></div>
                            <div class="h-3 w-3 rounded-full bg-slate-300"></div>
                            <div class="h-3 w-3 rounded-full bg-slate-300"></div>
                        </div>
                        <div class="h-5 w-32 rounded-full bg-slate-200/80" aria-hidden="true"></div>
                    </div>
                    <div class="flex flex-1 min-h-0 overflow-hidden">
                        <div class="flex w-16 flex-col items-center gap-4 border-r border-slate-100 bg-slate-50 py-4" aria-hidden="true">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-pm-accent/10">
                                <div class="h-4 w-4 rounded-sm bg-pm-accent"></div>
                            </div>
                            <div class="mt-2 h-6 w-6 rounded bg-slate-200"></div>
                            <div class="h-6 w-6 rounded bg-slate-200"></div>
                            <div class="h-6 w-6 rounded bg-slate-200"></div>
                        </div>
                        <div class="flex flex-1 flex-col gap-6 bg-white p-6" aria-hidden="true">
                            <div class="flex items-center justify-between">
                                <div class="h-6 w-32 rounded bg-slate-800"></div>
                                <div class="h-8 w-24 rounded-lg bg-pm-accent"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex h-24 flex-col justify-between rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="h-3 w-1/2 rounded bg-slate-300"></div>
                                    <div class="h-6 w-3/4 rounded bg-slate-800"></div>
                                </div>
                                <div class="flex h-24 flex-col justify-between rounded-xl border border-slate-100 bg-pm-accent/5 p-4">
                                    <div class="h-3 w-[80%] rounded bg-pm-accent/60"></div>
                                    <div class="h-6 w-full rounded bg-pm-accent/80"></div>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col gap-3">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="h-3 w-8 rounded bg-slate-200"></div>
                                    <div class="h-3 w-16 rounded bg-slate-200"></div>
                                    <div class="h-4 w-12 rounded-full bg-green-100"></div>
                                </div>
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="h-3 w-8 rounded bg-slate-200"></div>
                                    <div class="h-3 w-20 rounded bg-slate-200"></div>
                                    <div class="h-4 w-12 rounded-full bg-amber-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute -bottom-6 -left-8 z-20 flex w-64 max-w-[calc(100%-1rem)] items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-lg">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pm-accent/10" aria-hidden="true">
                        <svg class="h-5 w-5 text-pm-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-[11px] font-semibold uppercase tracking-wider text-slate-500">Новая бронь</div>
                        <div class="mt-0.5 truncate text-sm font-bold text-slate-900">Toyota Camry</div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1 text-green-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                        <span class="text-xs font-bold">Оплачено</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
