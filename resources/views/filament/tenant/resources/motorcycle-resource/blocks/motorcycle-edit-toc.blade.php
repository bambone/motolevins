@props([
    /** @var list<array{id: string, label: string, status: string, href: string}> $sections */
    'sections' => [],
])

@php
    $statusDot = function (string $s): string {
        return match ($s) {
            \App\Support\Motorcycle\MotorcycleEditCompleteness::STATUS_OK => 'fi-moto-toc-dot fi-moto-toc-dot--ok',
            \App\Support\Motorcycle\MotorcycleEditCompleteness::STATUS_WARN => 'fi-moto-toc-dot fi-moto-toc-dot--warn',
            default => 'fi-moto-toc-dot fi-moto-toc-dot--todo',
        };
    };
@endphp

@if ($sections !== [])
    <nav
        id="fi-moto-edit-toc"
        class="fi-moto-edit-toc fi-moto-edit-toc--bar fi-section relative py-3 lg:rounded-xl lg:border lg:border-gray-200/80 lg:px-4 lg:py-3 dark:lg:border-white/10"
        aria-label="Разделы карточки"
        data-moto-tab-query-key="{{ \App\Filament\Tenant\Forms\LinkedBookableSchedulingForm::MOTORCYCLE_TAB_QUERY_KEY }}"
        data-moto-tab-main="{{ \App\Filament\Tenant\Forms\LinkedBookableSchedulingForm::TAB_KEY_MAIN }}"
    >
        <div class="mb-2 flex items-center justify-between gap-2 lg:hidden">
            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Разделы</span>
        </div>
        <div class="fi-moto-toc-scroll -mx-1 flex gap-0.5 overflow-x-auto pb-1 lg:flex-wrap lg:gap-x-2 lg:gap-y-2 lg:overflow-visible lg:pb-0">
            @foreach ($sections as $row)
                <a
                    href="{{ $row['href'] }}"
                    data-moto-toc-target="{{ $row['id'] }}"
                    class="fi-moto-toc-link inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-transparent transition hover:bg-gray-100 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white lg:text-sm"
                >
                    <span class="{{ $statusDot($row['status']) }}" aria-hidden="true"></span>
                    <span class="whitespace-nowrap">{{ $row['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    @once
        <script>
            (function () {
                function tabConfig() {
                    const nav = document.getElementById('fi-moto-edit-toc');
                    const key = (nav && nav.dataset.motoTabQueryKey) || 'moto_edit_tab';
                    const main = (nav && nav.dataset.motoTabMain) || 'main';
                    return { key: key, main: main };
                }

                function parseMotoEditTab() {
                    const cfg = tabConfig();
                    const q = new URLSearchParams(window.location.search || '');
                    let t = (q.get(cfg.key) || '').toLowerCase();
                    if (!t) {
                        try {
                            const ref = document.referrer || '';
                            const idx = ref.indexOf('?');
                            if (idx !== -1) {
                                const sub = new URLSearchParams(ref.slice(idx + 1));
                                t = (sub.get(cfg.key) || '').toLowerCase();
                            }
                        } catch (e) {}
                    }
                    return t === '' || t === cfg.main ? cfg.main : t;
                }

                function bootMotoEditToc() {
                    const nav = document.getElementById('fi-moto-edit-toc');
                    if (!nav || nav.dataset.motoTocBound === '1') {
                        return;
                    }
                    nav.dataset.motoTocBound = '1';

                    const links = Array.from(nav.querySelectorAll('a.fi-moto-toc-link[href^="#"]'));
                    const ids = links
                        .map(function (a) {
                            return a.getAttribute('href').slice(1);
                        })
                        .filter(Boolean);

                    var io = null;
                    var ioThresholds = (function () {
                        var t = [];
                        var i;
                        for (i = 0; i <= 40; i++) {
                            t.push(i / 40);
                        }
                        return t;
                    })();

                    function spyThresholdPx() {
                        const topbar = document.querySelector('.fi-topbar');
                        const topbarH = topbar ? topbar.getBoundingClientRect().height : 0;
                        const toc = document.getElementById('fi-moto-edit-toc');
                        const tocH = toc ? toc.getBoundingClientRect().height : 0;
                        const sample = document.getElementById(ids[0] || '');
                        var scrollMarginPx = 120;
                        if (sample) {
                            var raw = getComputedStyle(sample).scrollMarginTop;
                            var n = parseFloat(raw);
                            if (!isNaN(n)) scrollMarginPx = n;
                        }
                        return Math.max(topbarH + tocH + 12, scrollMarginPx, 72);
                    }

                    function scrollableAncestors(el) {
                        const roots = [];
                        var n = el;
                        while (n && n.nodeType === 1) {
                            var st = window.getComputedStyle(n);
                            var oy = st.overflowY;
                            var all = st.overflow;
                            var yScroll =
                                oy === 'auto' ||
                                oy === 'scroll' ||
                                oy === 'overlay' ||
                                all === 'auto' ||
                                all === 'scroll';
                            if (yScroll && n.scrollHeight > n.clientHeight + 2) {
                                roots.push(n);
                            }
                            n = n.parentElement;
                        }
                        return roots;
                    }

                    function motoTocScrollRoots() {
                        var set = new Set();
                        scrollableAncestors(nav).forEach(function (x) {
                            set.add(x);
                        });
                        var first = document.getElementById(ids[0] || '');
                        if (first) {
                            scrollableAncestors(first).forEach(function (x) {
                                set.add(x);
                            });
                        }
                        ids.forEach(function (id) {
                            var el = document.getElementById(id);
                            if (el) {
                                scrollableAncestors(el).forEach(function (x) {
                                    set.add(x);
                                });
                            }
                        });
                        var se = document.scrollingElement;
                        if (se && se.scrollHeight > se.clientHeight + 2) {
                            set.add(se);
                        }
                        set.add(window);
                        return Array.from(set);
                    }

                    function setActive(id) {
                        links.forEach(function (a) {
                            var on = a.getAttribute('href') === '#' + id;
                            a.classList.toggle('fi-moto-toc-active', on);
                            a.setAttribute('aria-current', on ? 'true' : 'false');
                        });
                    }

                    function update() {
                        const cfg = tabConfig();
                        if (parseMotoEditTab() !== cfg.main) return;
                        const y = spyThresholdPx();
                        var current = ids[0] || null;
                        for (var i = 0; i < ids.length; i++) {
                            var id = ids[i];
                            var el = document.getElementById(id);
                            if (!el) continue;
                            var top = el.getBoundingClientRect().top;
                            if (top <= y) current = id;
                        }
                        if (current) setActive(current);
                    }

                    function bindIntersectionObserver() {
                        if (typeof IntersectionObserver === 'undefined') {
                            return;
                        }
                        if (io) {
                            io.disconnect();
                        }
                        io = new IntersectionObserver(
                            function () {
                                requestAnimationFrame(update);
                            },
                            { root: null, rootMargin: '0px', threshold: ioThresholds },
                        );
                        ids.forEach(function (id) {
                            var el = document.getElementById(id);
                            if (el) io.observe(el);
                        });
                    }

                    window.__motoTocSpyUpdate = function () {
                        bindIntersectionObserver();
                        update();
                    };

                    nav.addEventListener('click', function (e) {
                        var a = e.target && e.target.closest ? e.target.closest('a.fi-moto-toc-link') : null;
                        if (!a || !nav.contains(a)) return;
                        var href = a.getAttribute('href');
                        if (!href || href.charAt(0) !== '#') return;
                        var id = href.slice(1);
                        if (!id) return;
                        setActive(id);
                        requestAnimationFrame(update);
                    });

                    var ticking = false;
                    function onMotoTocScroll() {
                        if (ticking) return;
                        ticking = true;
                        requestAnimationFrame(function () {
                            ticking = false;
                            update();
                        });
                    }
                    motoTocScrollRoots().forEach(function (root) {
                        root.addEventListener('scroll', onMotoTocScroll, { passive: true });
                    });

                    window.addEventListener('hashchange', function () {
                        requestAnimationFrame(update);
                    });

                    window.addEventListener('resize', function () {
                        requestAnimationFrame(function () {
                            bindIntersectionObserver();
                            update();
                        });
                    });

                    (function bindLivewireSpyRefresh() {
                        function register() {
                            if (typeof Livewire === 'undefined' || !Livewire.hook) {
                                return;
                            }
                            Livewire.hook('message.processed', function () {
                                requestAnimationFrame(function () {
                                    if (typeof window.__motoTocSpyUpdate === 'function') {
                                        window.__motoTocSpyUpdate();
                                    }
                                });
                            });
                        }
                        if (typeof Livewire !== 'undefined' && Livewire.hook) {
                            register();
                        } else {
                            document.addEventListener('livewire:init', register, { once: true });
                        }
                    })();

                    bindIntersectionObserver();
                    update();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootMotoEditToc);
                } else {
                    requestAnimationFrame(bootMotoEditToc);
                }
            })();
        </script>
    @endonce
@endif
