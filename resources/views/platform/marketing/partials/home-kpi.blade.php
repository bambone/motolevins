<section id="statistika" class="pm-section-anchor relative overflow-hidden border-b border-slate-200 bg-slate-50 py-12 sm:py-16 md:py-20" aria-labelledby="statistika-heading">
    <!-- Telemetry dots background -->
    <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="relative z-10 mx-auto max-w-6xl px-3 sm:px-4 md:px-6">
        <h2 id="statistika-heading" class="fade-reveal text-balance text-xl font-bold leading-tight text-slate-900 sm:text-2xl md:text-3xl">{{ $pm['kpi_section_title'] ?? 'Платформа уже работает в бизнесе' }}</h2>
        <p class="fade-reveal mt-3 text-sm leading-relaxed text-slate-600 sm:text-base" style="transition-delay: 100ms;">{{ $pm['kpi_section_intro'] ?? 'Каждая метрика — про реальную работу системы.' }}</p>
        <div class="fade-reveal mt-8 grid gap-0 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:mt-10 md:grid-cols-3 md:divide-x md:divide-y-0" style="transition-delay: 200ms;">
            @foreach($pm['kpi'] ?? [] as $i => $row)
                <div class="fade-reveal pm-reveal-kpi-{{ min($i, 2) }} flex flex-col justify-center p-6 sm:p-8">
                    @if(!empty($row['eyebrow']))
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $row['eyebrow'] }}</p>
                    @endif
                    <div class="text-[clamp(2.5rem,4vw,3.5rem)] font-extrabold leading-none tracking-tight text-pm-accent">{{ $row['value'] }}</div>
                    @if(!empty($row['label']))
                        <div class="mt-2 text-sm font-bold uppercase tracking-wide text-slate-900">{{ $row['label'] }}</div>
                    @endif
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $row['why'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <p class="fade-reveal mt-8 text-center text-sm font-medium text-slate-700 sm:text-base" style="transition-delay: 500ms;">{{ $pm['kpi_section_footer'] ?? 'Это не концепт — это работающая система' }}</p>
    </div>
</section>
