@php
    /** @var string $label */
    /** @var string $sourceContext */
    /** @var string $goalPrefill */
    /** @var string $variant primary|secondary */
    /** @var string $scrollAnchor */
    $label = trim((string) ($label ?? ''));
    if ($label === '') {
        return;
    }
    $sourceContext = trim((string) ($sourceContext ?? ''));
    $goalPrefill = trim((string) ($goalPrefill ?? ''));
    $variant = trim((string) ($variant ?? 'primary')) ?: 'primary';
    $scrollAnchor = trim((string) ($scrollAnchor ?? ''));
    if ($scrollAnchor === '' || ! str_starts_with($scrollAnchor, '#')) {
        $scrollAnchor = '#expert-inquiry';
    }
    $tenant = tenant();
    if ($tenant === null) {
        return;
    }
    $cfg = \App\Tenant\Expert\TenantEnrollmentCtaConfig::forCurrent();
    if ($cfg === null) {
        return;
    }
    $mode = $cfg->mode();
    $pageUrl = url('/'.$cfg->enrollmentPageSlug());
    $isPrimary = $variant === 'primary';
    $btnBase = $isPrimary
        ? 'tenant-btn-primary group relative inline-flex min-h-14 w-full items-center justify-center gap-3 overflow-hidden rounded-xl px-8 text-[15px] font-bold shadow-xl transition-all hover:scale-[1.02] hover:shadow-moto-amber/20 sm:w-auto sm:px-10'
        : 'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border-2 border-moto-amber/55 bg-transparent px-6 text-[14px] font-bold text-white transition-all hover:bg-moto-amber/10 sm:w-auto sm:px-8';
@endphp
@if ($mode === \App\Tenant\Expert\TenantEnrollmentCtaConfig::MODE_SCROLL)
    <a
        href="{{ e($scrollAnchor) }}"
        class="{{ $btnBase }}"
        @if($goalPrefill !== '') data-rb-enrollment-scroll-goal="{{ e($goalPrefill) }}" @endif
    >
        <span class="relative z-10">{{ $label }}</span>
        @if($isPrimary)
            <span class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/10 transition-transform group-hover:translate-x-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </span>
        @endif
    </a>
@elseif ($mode === \App\Tenant\Expert\TenantEnrollmentCtaConfig::MODE_PAGE)
    <a href="{{ e($pageUrl) }}" class="{{ $btnBase }}">
        <span class="relative z-10">{{ $label }}</span>
        @if($isPrimary)
            <span class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/10 transition-transform group-hover:translate-x-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </span>
        @endif
    </a>
@else
    <button
        type="button"
        class="{{ $btnBase }} cursor-pointer border-0"
        data-rb-enrollment-generic-cta="1"
        @if($sourceContext !== '') data-rb-enrollment-source-context="{{ e($sourceContext) }}" @endif
        @if($goalPrefill !== '') data-rb-enrollment-goal-prefill="{{ e($goalPrefill) }}" @endif
    >
        <span class="relative z-10">{{ $label }}</span>
        @if($isPrimary)
            <span class="relative z-10 flex h-6 w-6 items-center justify-center rounded-full bg-black/10 transition-transform group-hover:translate-x-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </span>
        @endif
    </button>
@endif
