@props([
    'presentation',
    /** @var \App\PageBuilder\Contacts\ContactSectionPresentation $presentation */
])
@if($presentation->hasAddress() || $presentation->hasWorkingHours())
    <div class="mt-10 rounded-2xl border border-white/5 bg-white/[0.02] p-6 ring-1 ring-inset ring-white/5 sm:mt-12 sm:p-7">
        @if($presentation->hasAddress())
            <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-silver/50">Адрес и выдача</p>
            <div class="flex items-start gap-4 rounded-xl border border-white/8 bg-obsidian/40 p-5 ring-1 ring-inset ring-white/5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/5 text-moto-amber/90">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="min-w-0 text-sm leading-relaxed text-silver/80">{{ $presentation->address }}</p>
            </div>
        @endif
        @if($presentation->hasWorkingHours())
            <div class="@if($presentation->hasAddress()) mt-6 border-t border-white/5 pt-6 @endif">
                <x-custom-pages.contacts.working-hours :working-hours="$presentation->workingHours" />
            </div>
        @endif
    </div>
@endif
