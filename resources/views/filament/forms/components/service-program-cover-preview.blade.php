@php
    /** @var \Filament\Forms\Components\ViewField $field */
    $tiles = $tiles ?? [];
    $safeArea = $safeArea ?? ['bottomPercent' => 38, 'label' => ''];
    $bottomPct = (float) ($safeArea['bottomPercent'] ?? 38);
    $safeAreaHeightStyle = sprintf('height: %s%%;', e((string) $bottomPct));
    $editorConfig = $editorConfig ?? [];
    $previewKey = $previewKey ?? '';
    $overlayMobile = $overlayMobile ?? ['svc-program-mask-fade-start' => '52%', 'svc-program-mask-fade-mid' => '70%'];
    $overlayDesktop = $overlayDesktop ?? ['svc-program-mask-fade-start' => '55%', 'svc-program-mask-fade-mid' => '72%'];
    $fieldId = $field->getId();

    $byKey = collect($tiles)->keyBy(fn ($t) => (string) ($t['key'] ?? ''));
    $tileMobile = $byKey->get('mobile', []);
    $tileTablet = $byKey->get('tablet', []);
    $tileDesktop = $byKey->get('desktop', []);
@endphp

{{-- JS: resources/js/service-program-cover-focal-editor.js подключается глобально в AdminPanelProvider (HEAD_END),
     иначе при Livewire-морфе модалки Page Builder скрипт из ViewField не гарантирует регистрацию Alpine до x-data. --}}

<x-dynamic-component :component="$field->getFieldWrapperView()" :field="$field">
    <div
        wire:key="svc-cover-preview-{{ $previewKey }}"
        class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/80 p-3 dark:border-white/10 dark:bg-white/5"
        x-data="serviceProgramCoverFocalEditor(@js($editorConfig))"
    >
        <div class="flex flex-wrap items-center gap-2 gap-y-1 text-xs text-gray-600 dark:text-gray-400">
            <span class="font-semibold text-gray-800 dark:text-gray-200">Кадрирование</span>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="resetBoth()" x-show="sync">Сбросить все кадры к умолчанию</button>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="resetMobile()" x-show="!sync">Сбросить mobile</button>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="resetTablet()">Сбросить tablet</button>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="resetDesktop()" x-show="!sync">Сбросить desktop</button>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="copyToDesktop()">Копировать mobile → desktop</button>
            <button type="button" class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-2 py-1 text-xs" @click="copyToMobile()">Копировать desktop → mobile</button>
        </div>

        <p class="text-xs text-gray-600 dark:text-gray-400">
            Перетащите по рамке превью (курсор «рука»): события идут с рамки, не с самого файла в браузере. Если по оси нет запаса — увеличьте Zoom, затем снова сдвиньте. Клавиатура: клик по рамке, стрелки ±1%, Shift+стрелки ±5%. Синхронизация mobile/desktop не затрагивает tablet.
        </p>

        <div class="flex flex-wrap gap-4">
            {{-- Mobile (editable) --}}
            @php
                $w = (int) ($tileMobile['width'] ?? 360);
                $h = (int) ($tileMobile['height'] ?? 200);
                $src = $tileMobile['src'] ?? null;
                $srcOk = filled($src);
                $fadeStart = $overlayMobile['svc-program-mask-fade-start'] ?? '52%';
                $fadeMid = $overlayMobile['svc-program-mask-fade-mid'] ?? '70%';
                $mobileFrameStyle = sprintf('width: %dpx; max-width: 100%%; aspect-ratio: %d / %d;', $w, $w, $h);
                $mobileGradientStyle = sprintf(
                    'background: linear-gradient(to bottom, transparent 0%%, transparent 35%%, rgba(0,0,0,0.05) 50%%, rgba(0,0,0,0.2) %s, rgba(0,0,0,0.45) 100%%);',
                    e((string) $fadeMid)
                );
            @endphp
            <div class="min-w-0">
                <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Mobile</p>
                <p class="mb-1 text-[10px] text-gray-500">{{ $tileMobile['sourceLabel'] ?? '' }}</p>
                <div
                    class="svc-program-focal-frame touch-none relative overflow-hidden rounded-md border border-gray-200 bg-gray-900/5 dark:border-white/10"
                    :class="dragging &amp;&amp; dragging.key === 'mobile' ? 'cursor-grabbing' : 'cursor-grab'"
                    style="<?php echo e($mobileFrameStyle); ?>"
                    x-init="frameRefs.mobile = $el"
                    tabindex="0"
                    role="group"
                    aria-label="Кадрирование обложки mobile: перетащите изображение"
                    @pointerdown.capture="startDrag('mobile', $event)"
                    @keydown="if ($event.target === $el) { const s = $event.shiftKey; const k = $event.key; if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(k)) { $event.preventDefault(); let dx = 0, dy = 0; if (k === 'ArrowLeft') { dx = -1; } if (k === 'ArrowRight') { dx = 1; } if (k === 'ArrowUp') { dy = -1; } if (k === 'ArrowDown') { dy = 1; } nudge('mobile', dx, dy, s); } }"
                >
                    <div x-show="!canDrag('mobile')" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 text-[10px] text-gray-600 dark:bg-gray-900/80 dark:text-gray-400">Загрузка изображения…</div>
                    @if ($srcOk)
                        <div class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div class="pointer-events-none absolute inset-0" x-bind:style="layerTransformStyle('mobile')">
                                <img
                                    src="{{ e($src) }}"
                                    alt=""
                                    class="svc-program-focal-img pointer-events-none h-full w-full select-none object-cover"
                                    x-bind:style="{ objectPosition: objectPositionStyle('mobile') }"
                                    draggable="false"
                                    loading="lazy"
                                    x-on:load="onImgLoad('mobile', $event)"
                                    x-on:error="onImgError('mobile')"
                                />
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 border-t border-dashed border-amber-500/60 bg-amber-500/10" style="<?php echo e($safeAreaHeightStyle); ?>" title="{{ e($safeArea['label'] ?? '') }}"></div>
                        <div class="pointer-events-none absolute inset-0 opacity-85" style="<?php echo e($mobileGradientStyle); ?>"></div>
                    @else
                        <div class="flex h-full min-h-[4rem] items-center justify-center p-2 text-center text-[10px] text-gray-400">Нет изображения для превью</div>
                    @endif
                </div>
                <p class="mt-1 text-[10px] leading-snug text-amber-800/90 dark:text-amber-200/90" x-show="axisSlackHint('mobile') !== ''" x-text="axisSlackHint('mobile')"></p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <label class="text-[10px] font-medium text-gray-600 dark:text-gray-400" for="{{ $fieldId }}-focal-zoom-mobile">Zoom</label>
                    <input
                        id="{{ $fieldId }}-focal-zoom-mobile"
                        name="{{ $fieldId }}-focal-zoom-mobile"
                        type="range"
                        class="h-2 w-36 max-w-full cursor-pointer accent-amber-600"
                        :min="scaleBounds().min"
                        :max="scaleBounds().max"
                        :step="scaleBounds().step"
                        :value="local.mobile.s"
                        @input="onScaleInput('mobile', $event.target.value)"
                        @change="commitScaleFromSlider()"
                    />
                    <span class="text-[10px] font-mono text-gray-700 dark:text-gray-200" x-text="(local.mobile.s ?? 1).toFixed(2) + '×'"></span>
                </div>
                <p class="mt-0.5 text-[10px] text-gray-500">Фокус <span x-text="local.mobile.x.toFixed(1)"></span>% × <span x-text="local.mobile.y.toFixed(1)"></span>%</p>
            </div>

            {{-- Tablet (editable; не участвует в sync mobile↔desktop) --}}
            @php
                $w = (int) ($tileTablet['width'] ?? 600);
                $h = (int) ($tileTablet['height'] ?? 120);
                $src = $tileTablet['src'] ?? null;
                $srcOk = filled($src);
                $fadeMid = $overlayMobile['svc-program-mask-fade-mid'] ?? '70%';
                $tabletFrameStyle = sprintf('width: %dpx; max-width: 100%%; aspect-ratio: %d / %d;', $w, $w, $h);
                $tabletGradientStyle = sprintf(
                    'background: linear-gradient(to bottom, transparent 0%%, transparent 35%%, rgba(0,0,0,0.05) 50%%, rgba(0,0,0,0.2) %s, rgba(0,0,0,0.45) 100%%);',
                    e((string) $fadeMid)
                );
            @endphp
            <div class="min-w-0">
                <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tablet</p>
                <p class="mb-1 text-[10px] text-gray-500">{{ $tileTablet['sourceLabel'] ?? '' }}</p>
                <div
                    class="svc-program-focal-frame touch-none relative overflow-hidden rounded-md border border-gray-200 bg-gray-900/5 dark:border-white/10"
                    :class="dragging &amp;&amp; dragging.key === 'tablet' ? 'cursor-grabbing' : 'cursor-grab'"
                    style="<?php echo e($tabletFrameStyle); ?>"
                    x-init="frameRefs.tablet = $el"
                    tabindex="0"
                    role="group"
                    aria-label="Кадрирование обложки tablet: перетащите изображение"
                    @pointerdown.capture="startDrag('tablet', $event)"
                    @keydown="if ($event.target === $el) { const s = $event.shiftKey; const k = $event.key; if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(k)) { $event.preventDefault(); let dx = 0, dy = 0; if (k === 'ArrowLeft') { dx = -1; } if (k === 'ArrowRight') { dx = 1; } if (k === 'ArrowUp') { dy = -1; } if (k === 'ArrowDown') { dy = 1; } nudge('tablet', dx, dy, s); } }"
                >
                    <div x-show="!canDrag('tablet')" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 text-[10px] text-gray-600 dark:bg-gray-900/80 dark:text-gray-400">Загрузка изображения…</div>
                    @if ($srcOk)
                        <div class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div class="pointer-events-none absolute inset-0" x-bind:style="layerTransformStyle('tablet')">
                                <img
                                    src="{{ e($src) }}"
                                    alt=""
                                    class="svc-program-focal-img pointer-events-none h-full w-full select-none object-cover"
                                    x-bind:style="{ objectPosition: objectPositionStyle('tablet') }"
                                    draggable="false"
                                    loading="lazy"
                                    x-on:load="onImgLoad('tablet', $event)"
                                    x-on:error="onImgError('tablet')"
                                />
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 border-t border-dashed border-amber-500/60 bg-amber-500/10" style="<?php echo e($safeAreaHeightStyle); ?>" title="{{ e($safeArea['label'] ?? '') }}"></div>
                        <div class="pointer-events-none absolute inset-0 opacity-85" style="<?php echo e($tabletGradientStyle); ?>"></div>
                    @else
                        <div class="flex h-full min-h-[4rem] items-center justify-center p-2 text-center text-[10px] text-gray-400">Нет изображения для превью</div>
                    @endif
                </div>
                <p class="mt-1 text-[10px] leading-snug text-amber-800/90 dark:text-amber-200/90" x-show="axisSlackHint('tablet') !== ''" x-text="axisSlackHint('tablet')"></p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <label class="text-[10px] font-medium text-gray-600 dark:text-gray-400" for="{{ $fieldId }}-focal-zoom-tablet">Zoom</label>
                    <input
                        id="{{ $fieldId }}-focal-zoom-tablet"
                        name="{{ $fieldId }}-focal-zoom-tablet"
                        type="range"
                        class="h-2 w-36 max-w-full cursor-pointer accent-amber-600"
                        :min="scaleBounds().min"
                        :max="scaleBounds().max"
                        :step="scaleBounds().step"
                        :value="local.tablet.s"
                        @input="onScaleInput('tablet', $event.target.value)"
                        @change="commitScaleFromSlider()"
                    />
                    <span class="text-[10px] font-mono text-gray-700 dark:text-gray-200" x-text="(local.tablet.s ?? 1).toFixed(2) + '×'"></span>
                </div>
                <p class="mt-0.5 text-[10px] text-gray-500">Фокус <span x-text="local.tablet.x.toFixed(1)"></span>% × <span x-text="local.tablet.y.toFixed(1)"></span>%</p>
            </div>

            {{-- Desktop (editable) --}}
            @php
                $w = (int) ($tileDesktop['width'] ?? 900);
                $h = (int) ($tileDesktop['height'] ?? 120);
                $src = $tileDesktop['src'] ?? null;
                $srcOk = filled($src);
                $fadeMid = $overlayDesktop['svc-program-mask-fade-mid'] ?? '72%';
                $desktopFrameStyle = sprintf('width: %dpx; max-width: 100%%; aspect-ratio: %d / %d;', $w, $w, $h);
                $desktopGradientStyle = sprintf(
                    'background: linear-gradient(to bottom, transparent 0%%, transparent 35%%, rgba(0,0,0,0.05) 50%%, rgba(0,0,0,0.2) %s, rgba(0,0,0,0.45) 100%%);',
                    e((string) $fadeMid)
                );
            @endphp
            <div class="min-w-0">
                <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Desktop</p>
                <p class="mb-1 text-[10px] text-gray-500">{{ $tileDesktop['sourceLabel'] ?? '' }}</p>
                <div
                    class="svc-program-focal-frame touch-none relative overflow-hidden rounded-md border border-gray-200 bg-gray-900/5 dark:border-white/10"
                    :class="dragging &amp;&amp; dragging.key === 'desktop' ? 'cursor-grabbing' : 'cursor-grab'"
                    style="<?php echo e($desktopFrameStyle); ?>"
                    x-init="frameRefs.desktop = $el"
                    tabindex="0"
                    role="group"
                    aria-label="Кадрирование обложки desktop: перетащите изображение"
                    @pointerdown.capture="startDrag('desktop', $event)"
                    @keydown="if ($event.target === $el) { const s = $event.shiftKey; const k = $event.key; if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(k)) { $event.preventDefault(); let dx = 0, dy = 0; if (k === 'ArrowLeft') { dx = -1; } if (k === 'ArrowRight') { dx = 1; } if (k === 'ArrowUp') { dy = -1; } if (k === 'ArrowDown') { dy = 1; } nudge('desktop', dx, dy, s); } }"
                >
                    <div x-show="!canDrag('desktop')" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 text-[10px] text-gray-600 dark:bg-gray-900/80 dark:text-gray-400">Загрузка изображения…</div>
                    @if ($srcOk)
                        <div class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div class="pointer-events-none absolute inset-0" x-bind:style="layerTransformStyle('desktop')">
                                <img
                                    src="{{ e($src) }}"
                                    alt=""
                                    class="svc-program-focal-img pointer-events-none h-full w-full select-none object-cover"
                                    x-bind:style="{ objectPosition: objectPositionStyle('desktop') }"
                                    draggable="false"
                                    loading="lazy"
                                    x-on:load="onImgLoad('desktop', $event)"
                                    x-on:error="onImgError('desktop')"
                                />
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 border-t border-dashed border-amber-500/60 bg-amber-500/10" style="<?php echo e($safeAreaHeightStyle); ?>" title="{{ e($safeArea['label'] ?? '') }}"></div>
                        <div class="pointer-events-none absolute inset-0 opacity-85" style="<?php echo e($desktopGradientStyle); ?>"></div>
                    @else
                        <div class="flex h-full min-h-[4rem] items-center justify-center p-2 text-center text-[10px] text-gray-400">Нет изображения для превью</div>
                    @endif
                </div>
                <p class="mt-1 text-[10px] leading-snug text-amber-800/90 dark:text-amber-200/90" x-show="axisSlackHint('desktop') !== ''" x-text="axisSlackHint('desktop')"></p>
                <div class="mt-1 flex flex-wrap items-center gap-2" x-show="!sync">
                    <label class="text-[10px] font-medium text-gray-600 dark:text-gray-400" for="{{ $fieldId }}-focal-zoom-desktop">Zoom</label>
                    <input
                        id="{{ $fieldId }}-focal-zoom-desktop"
                        name="{{ $fieldId }}-focal-zoom-desktop"
                        type="range"
                        class="h-2 w-36 max-w-full cursor-pointer accent-amber-600"
                        :min="scaleBounds().min"
                        :max="scaleBounds().max"
                        :step="scaleBounds().step"
                        :value="local.desktop.s"
                        @input="onScaleInput('desktop', $event.target.value)"
                        @change="commitScaleFromSlider()"
                    />
                    <span class="text-[10px] font-mono text-gray-700 dark:text-gray-200" x-text="(local.desktop.s ?? 1).toFixed(2) + '×'"></span>
                </div>
                <p class="mt-1 text-[10px] text-gray-500" x-show="sync">Zoom синхронизирован с колонкой Mobile.</p>
                <p class="mt-0.5 text-[10px] text-gray-500">Фокус <span x-text="local.desktop.x.toFixed(1)"></span>% × <span x-text="local.desktop.y.toFixed(1)"></span>%</p>
            </div>
        </div>
    </div>
</x-dynamic-component>
