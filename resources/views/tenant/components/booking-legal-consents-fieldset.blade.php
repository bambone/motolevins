@props([
    'rentalLegalUrls' => [],
    'variant' => 'checkout',
])
@php
    $ui = $tenantBookingConsentUi ?? ['show' => false, 'items' => []];
    $showDynamic = ! empty($ui['show']);
    $termsUrl = $rentalLegalUrls['terms_url'] ?? route('terms');
    $privacyUrl = $rentalLegalUrls['privacy_url'] ?? route('privacy');
    $contractPdfUrl = $rentalLegalUrls['contract_pdf_url'] ?? '';
@endphp
@if ($showDynamic)
    @if ($variant === 'checkout')
        <fieldset class="mt-8 space-y-4 rounded-xl border border-white/10 bg-black/25 p-4 sm:p-5">
            <legend class="mb-1 px-1 text-sm font-semibold text-white">Перед бронированием</legend>
            @foreach ($ui['items'] as $item)
                @php($cid = (int) ($item['id'] ?? 0))
                <div class="flex gap-3">
                    <input
                        id="checkout-consent-{{ $cid }}"
                        type="checkbox"
                        name="consent_accepted[{{ $cid }}]"
                        value="1"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40"
                        {{ old('consent_accepted.'.$cid) ? 'checked' : '' }}
                        @if (! empty($item['is_required'])) required @endif
                    >
                    <label for="checkout-consent-{{ $cid }}" class="min-w-0 text-sm leading-relaxed text-zinc-300">
                        {!! nl2br(e($item['label'] ?? '')) !!}
                        @if (! empty($item['link_text']) && ! empty($item['link_url']))
                            <a href="{{ $item['link_url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-moto-amber underline-offset-2 hover:underline">{{ $item['link_text'] }}</a>
                        @endif
                        @if (! empty($item['is_required']))
                            <span class="text-red-400">*</span>
                        @endif
                    </label>
                </div>
                @error('consent_accepted.'.$cid)
                    <p class="text-sm text-red-400">{{ $message }}</p>
                @enderror
            @endforeach
            @error('consent_accepted')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror
        </fieldset>
    @else
        <fieldset class="mt-6 space-y-4 rounded-xl border border-white/10 bg-black/25 p-4 sm:p-5" x-ref="bookingLegalConsents">
            <legend class="mb-1 w-full px-0.5 text-sm font-semibold text-white">Перед отправкой заявки</legend>
            @foreach ($ui['items'] as $item)
                @php($cid = (int) ($item['id'] ?? 0))
                <div class="flex gap-3">
                    <input
                        id="booking-modal-consent-{{ $cid }}"
                        type="checkbox"
                        name="consent_accepted[{{ $cid }}]"
                        value="1"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40"
                        @if (! empty($item['is_required'])) required @endif
                    >
                    <label for="booking-modal-consent-{{ $cid }}" class="min-w-0 text-sm leading-relaxed text-zinc-300">
                        {!! nl2br(e($item['label'] ?? '')) !!}
                        @if (! empty($item['link_text']) && ! empty($item['link_url']))
                            <a href="{{ $item['link_url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-moto-amber underline-offset-2 hover:underline">{{ $item['link_text'] }}</a>
                        @endif
                        @if (! empty($item['is_required']))
                            <span class="text-red-400">*</span>
                        @endif
                    </label>
                </div>
            @endforeach
        </fieldset>
    @endif
@elseif ($variant === 'checkout')
<fieldset class="mt-8 space-y-4 rounded-xl border border-white/10 bg-black/25 p-4 sm:p-5">
    <legend class="mb-1 px-1 text-sm font-semibold text-white">Перед бронированием</legend>
    <div class="flex gap-3">
        <input id="checkout-agree-terms" type="checkbox" name="agree_to_terms" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40" {{ old('agree_to_terms') ? 'checked' : '' }} required>
        <label for="checkout-agree-terms" class="min-w-0 text-sm leading-relaxed text-zinc-300">
            Я ознакомился с <a href="{{ $termsUrl }}" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">условиями проката</a>@if($contractPdfUrl !== '')
                и <a href="{{ $contractPdfUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">договором проката</a> (PDF)@endif
            и соглашаюсь с ними. <span class="text-red-400">*</span>
        </label>
    </div>
    @error('agree_to_terms')
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror
    <div class="flex gap-3">
        <input id="checkout-agree-privacy" type="checkbox" name="agree_to_privacy" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40" {{ old('agree_to_privacy') ? 'checked' : '' }} required>
        <label for="checkout-agree-privacy" class="min-w-0 text-sm leading-relaxed text-zinc-300">
            Я согласен на <a href="{{ $privacyUrl }}" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">обработку персональных данных</a> в соответствии с политикой конфиденциальности. <span class="text-red-400">*</span>
        </label>
    </div>
    @error('agree_to_privacy')
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror
</fieldset>
@else
{{-- modal: legacy Alpine form.agree_to_terms / form.agree_to_privacy --}}
<fieldset class="mt-6 space-y-4 rounded-xl border border-white/10 bg-black/25 p-4 sm:p-5" x-ref="bookingLegalConsents">
    <legend class="mb-1 w-full px-0.5 text-sm font-semibold text-white">Перед отправкой заявки</legend>
    <div class="flex gap-3">
        <input id="booking-modal-agree-terms" type="checkbox" class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40"
               x-model="form.agree_to_terms">
        <label for="booking-modal-agree-terms" class="min-w-0 text-sm leading-relaxed text-zinc-300">
            Я ознакомился с <a href="{{ $termsUrl }}" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">условиями проката</a>@if($contractPdfUrl !== '')
                и <a href="{{ $contractPdfUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">договором проката</a> (PDF)@endif
            и соглашаюсь с ними. <span class="text-red-400">*</span>
        </label>
    </div>
    <div class="flex gap-3">
        <input id="booking-modal-agree-privacy" type="checkbox" class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 text-moto-amber focus:ring-moto-amber/40"
               x-model="form.agree_to_privacy">
        <label for="booking-modal-agree-privacy" class="min-w-0 text-sm leading-relaxed text-zinc-300">
            Я согласен на <a href="{{ $privacyUrl }}" class="font-medium text-moto-amber underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moto-amber">обработку персональных данных</a> в соответствии с политикой конфиденциальности. <span class="text-red-400">*</span>
        </label>
    </div>
</fieldset>
@endif
