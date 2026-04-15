@php
    /** @var array<string, mixed> $data */
    $data = is_array($data ?? null) ? $data : [];
    if (array_key_exists('enabled', $data) && filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) === false) {
        return;
    }
    $sid = trim((string) ($data['section_id'] ?? ''));
    $h = trim((string) ($data['heading'] ?? ''));
    $lead = trim((string) ($data['lead'] ?? ''));
    $btn = trim((string) ($data['button_label'] ?? '')) ?: 'Записаться';
    $sourceContext = trim((string) ($data['source_context'] ?? '')) ?: 'enrollment_cta_strip';
    $goalPrefill = trim((string) ($data['goal_prefill'] ?? ''));
    if ($h === '' && $lead === '') {
        return;
    }
@endphp
<section @if($sid !== '') id="{{ e($sid) }}" @endif class="relative mb-10 min-w-0 scroll-mt-24 sm:mb-14 sm:scroll-mt-28" data-page-section-type="{{ $section->section_type ?? '' }}">
    <div class="relative overflow-hidden rounded-[1.35rem] border border-moto-amber/25 bg-gradient-to-br from-moto-amber/[0.12] to-white/[0.04] px-5 py-6 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] ring-1 ring-inset ring-white/[0.06] sm:rounded-[1.75rem] sm:px-8 sm:py-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
            <div class="min-w-0 flex-1">
                @if($h !== '')
                    <h2 class="text-balance text-xl font-extrabold tracking-tight text-white/95 sm:text-2xl">{{ $h }}</h2>
                @endif
                @if($lead !== '')
                    <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-silver/85 sm:mt-4 sm:text-[17px]">{{ $lead }}</p>
                @endif
            </div>
            <div class="flex shrink-0 justify-stretch lg:justify-end">
                @include('tenant.partials.enrollment-cta-control', [
                    'label' => $btn,
                    'sourceContext' => $sourceContext,
                    'goalPrefill' => $goalPrefill,
                    'variant' => 'primary',
                    'scrollAnchor' => '#expert-inquiry',
                ])
            </div>
        </div>
    </div>
</section>
