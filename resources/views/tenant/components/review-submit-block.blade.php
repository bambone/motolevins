@php
    $cfg = $tenantReviewSubmitConfig ?? null;
    if ($cfg === null || ! $cfg->publicSubmitEnabled) {
        return;
    }
    $blockId = $blockId ?? 'rb-review-'.substr(sha1((string) ($pageUrl ?? request()->path()).($sectionSuffix ?? '')), 0, 12);
    $endpoint = route('api.tenant.reviews.submit');
    $pageUrl = $pageUrl ?? request()->getRequestUri();
@endphp
<div class="rb-review-submit-wrap mt-8 min-w-0 sm:mt-10" data-rb-review-submit-root>
    <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <p class="text-sm leading-relaxed text-silver/90 sm:text-base">
            Уже были у нас? Поделитесь впечатлением — это помогает другим посетителям.
        </p>
        <button
            type="button"
            class="tenant-btn-primary inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl px-6 py-2.5 text-sm font-bold shadow-lg sm:text-[15px]"
            data-rb-review-open="{{ e($blockId) }}"
        >
            Оставить отзыв
        </button>
    </div>

    <dialog
        id="{{ e($blockId) }}"
        class="rb-review-dialog fixed left-1/2 top-1/2 z-[80] w-[min(100vw-1.5rem,28rem)] max-h-[min(92vh,40rem)] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-white/15 bg-[#0c0f17] p-0 text-white shadow-2xl ring-1 ring-white/10 backdrop:bg-black/70 sm:w-[min(100vw-2rem,32rem)]"
        aria-labelledby="{{ e($blockId) }}-title"
    >
        <div class="border-b border-white/10 px-5 py-4 sm:px-6">
            <h2 id="{{ e($blockId) }}-title" class="text-lg font-bold leading-tight">Ваш отзыв</h2>
            <p class="mt-1 text-xs text-silver/75">Поля с отметкой обязательны. Email не публикуется на сайте.</p>
        </div>
        <div class="px-5 py-4 sm:px-6">
            <div
                class="hidden rounded-xl border border-emerald-500/35 bg-emerald-500/10 px-4 py-3 text-sm leading-snug text-emerald-100"
                data-rb-review-success
                role="status"
                aria-live="polite"
            ></div>
            <div
                class="mb-4 hidden rounded-xl border border-red-500/35 bg-red-500/10 px-4 py-3 text-sm leading-snug text-red-100"
                data-rb-review-alert
                role="alert"
                aria-live="assertive"
            ></div>

            <form
                class="space-y-4"
                novalidate
                data-rb-review-submit-form
                data-rb-review-endpoint="{{ e($endpoint) }}"
            >
                @csrf
                <input type="hidden" name="page_url" value="{{ e($pageUrl) }}">

                <div class="pointer-events-none fixed left-[-4000px] top-0 h-px w-px overflow-hidden opacity-0" aria-hidden="true">
                    <label for="{{ e($blockId) }}-hp">Не заполнять</label>
                    <input id="{{ e($blockId) }}-hp" type="text" name="website" tabindex="-1" autocomplete="off" value="">
                </div>

                <div data-rb-public-field="name">
                    <label for="{{ e($blockId) }}-name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-silver/80">Имя <span class="text-red-400">*</span></label>
                    <input
                        id="{{ e($blockId) }}-name"
                        name="name"
                        type="text"
                        required
                        maxlength="120"
                        autocomplete="name"
                        class="w-full rounded-xl border border-white/12 bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder:text-silver/40 focus:border-moto-amber/50 focus:outline-none focus:ring-2 focus:ring-moto-amber/25"
                        placeholder="Как к вам обращаться"
                    >
                </div>

                <div data-rb-public-field="body">
                    <label for="{{ e($blockId) }}-body" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-silver/80">Текст отзыва <span class="text-red-400">*</span></label>
                    <textarea
                        id="{{ e($blockId) }}-body"
                        name="body"
                        required
                        minlength="20"
                        maxlength="8000"
                        rows="5"
                        class="w-full rounded-xl border border-white/12 bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder:text-silver/40 focus:border-moto-amber/50 focus:outline-none focus:ring-2 focus:ring-moto-amber/25"
                        placeholder="Расскажите, как всё прошло (не менее 20 символов)"
                    ></textarea>
                </div>

                @if($cfg->showRatingField)
                    <div data-rb-public-field="rating">
                        <label for="{{ e($blockId) }}-rating" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-silver/80">Оценка <span class="text-red-400">*</span></label>
                        <select
                            id="{{ e($blockId) }}-rating"
                            name="rating"
                            required
                            class="w-full rounded-xl border border-white/12 bg-[#0c0f17] px-3 py-2.5 text-sm text-white focus:border-moto-amber/50 focus:outline-none focus:ring-2 focus:ring-moto-amber/25"
                        >
                            <option value="" disabled selected>Выберите оценку</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ $i }} — {{ $i === 5 ? 'отлично' : ($i === 4 ? 'хорошо' : ($i === 3 ? 'нормально' : ($i === 2 ? 'слабо' : 'плохо'))) }}</option>
                            @endfor
                        </select>
                    </div>
                @endif

                <div data-rb-public-field="city">
                    <label for="{{ e($blockId) }}-city" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-silver/80">Город</label>
                    <input
                        id="{{ e($blockId) }}-city"
                        name="city"
                        type="text"
                        maxlength="120"
                        class="w-full rounded-xl border border-white/12 bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder:text-silver/40 focus:border-moto-amber/50 focus:outline-none focus:ring-2 focus:ring-moto-amber/25"
                        placeholder="Необязательно"
                    >
                </div>

                <div data-rb-public-field="contact_email">
                    <label for="{{ e($blockId) }}-email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-silver/80">Email для связи</label>
                    <input
                        id="{{ e($blockId) }}-email"
                        name="contact_email"
                        type="email"
                        maxlength="255"
                        autocomplete="email"
                        class="w-full rounded-xl border border-white/12 bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder:text-silver/40 focus:border-moto-amber/50 focus:outline-none focus:ring-2 focus:ring-moto-amber/25"
                        placeholder="Не публикуется, по желанию"
                    >
                </div>

                <div data-rb-public-field="consent" class="flex gap-3 pt-1">
                    <input
                        id="{{ e($blockId) }}-consent"
                        name="consent"
                        type="checkbox"
                        value="1"
                        required
                        class="mt-1 h-4 w-4 shrink-0 rounded border-white/20 bg-white/[0.06] text-moto-amber focus:ring-moto-amber/40"
                    >
                    <label for="{{ e($blockId) }}-consent" class="text-xs leading-snug text-silver/85">
                        Согласен(на) на обработку персональных данных и публикацию отзыва на сайте в соответствии с политикой конфиденциальности. <span class="text-red-400">*</span>
                    </label>
                </div>

                <div class="flex flex-col gap-3 border-t border-white/10 pt-4 sm:flex-row sm:justify-end">
                    <button type="button" class="min-h-11 rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-silver hover:bg-white/5" data-rb-review-cancel>
                        Закрыть
                    </button>
                    <button type="submit" class="tenant-btn-primary min-h-11 rounded-xl px-6 py-2.5 text-sm font-bold" data-rb-review-send>
                        Отправить отзыв
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</div>

@once('rb-review-dialog-delegation')
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-rb-review-open]');
            if (!btn) return;
            var id = btn.getAttribute('data-rb-review-open');
            if (!id) return;
            var dlg = document.getElementById(id);
            if (dlg && typeof dlg.showModal === 'function') {
                dlg.showModal();
            }
        });
        document.addEventListener('click', function (e) {
            var t = e.target;
            if (t && t.hasAttribute && t.hasAttribute('data-rb-review-cancel')) {
                var dlg = t.closest('dialog');
                if (dlg && typeof dlg.close === 'function') dlg.close();
            }
        });
    </script>
@endonce
