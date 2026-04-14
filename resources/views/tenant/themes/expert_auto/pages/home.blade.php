@php
    $sections = $sections ?? [];
    $homeLayoutSections = $homeLayoutSections ?? collect();
@endphp
@extends('tenant.layouts.app')

@section('content')
    <div class="expert-home w-full min-w-0 pb-24 lg:pb-8">
        {{-- Отступ под фиксированную шапку (h 4.5rem / 5rem / 5.5rem) + небольшой зазор --}}
        <div
            class="expert-home-main mx-auto max-w-[min(88rem,calc(100vw-1.5rem))] px-3 pt-[calc(3.75rem+0.5rem)] sm:px-4 md:px-8 md:pt-[calc(5rem+0.75rem)] lg:px-12 lg:pt-[calc(5.5rem+1rem)]"
            data-expert-home-lazy-root="1"
        >
            @forelse ($homeLayoutSections as $section)
                @php
                    $sk = (string) ($section->section_key ?? '');
                    $skClass = $sk !== '' ? 'expert-home-section--'.preg_replace('/[^a-z0-9_-]+/i', '-', $sk) : 'expert-home-section--unknown';
                    $lazyAnchorId = '';
                    if ($sk === 'expert_lead_form') {
                        $dj = is_array($section->data_json ?? null) ? $section->data_json : [];
                        $lazyAnchorId = trim((string) ($dj['section_id'] ?? ''));
                        if ($lazyAnchorId === '') {
                            $lazyAnchorId = 'expert-inquiry';
                        }
                    }
                    $slotVars = [
                        'section' => $section,
                        'bikes' => $bikes ?? collect(),
                        'badges' => $badges ?? [],
                        'faqs' => $faqs ?? collect(),
                        'reviews' => $reviews ?? collect(),
                    ];
                @endphp
                @if ($loop->first)
                    <div class="expert-home-section expert-home-section--eager {{ $skClass }}" data-section-key="{{ e($sk) }}">
                        @include('tenant.pages.partials.home-section-slot', $slotVars)
                    </div>
                @else
                    <template id="expert-home-section-tpl-{{ (int) $section->id }}">
                        <div class="expert-home-section expert-home-section--lazy-mounted {{ $skClass }}" data-section-key="{{ e($sk) }}">
                            @include('tenant.pages.partials.home-section-slot', $slotVars)
                        </div>
                    </template>
                    <div
                        class="expert-home-section__lazy-host {{ $skClass }}"
                        id="expert-home-section-host-{{ (int) $section->id }}"
                        data-expert-lazy-template="expert-home-section-tpl-{{ (int) $section->id }}"
                        data-expert-lazy-order="{{ (int) $loop->index }}"
                        @if ($lazyAnchorId !== '')
                            data-expert-lazy-anchor="{{ e($lazyAnchorId) }}"
                        @endif
                        aria-busy="true"
                    >
                        <div class="expert-home-section__skeleton" aria-hidden="true">
                            <span class="expert-home-section__skeleton-shimmer"></span>
                        </div>
                    </div>
                @endif
            @empty
                <p class="text-center text-silver">Главная страница ещё не настроена.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('tenant-scripts')
    <script>
        (function () {
            var root = document.querySelector('[data-expert-home-lazy-root="1"]');
            if (!root) {
                return;
            }
            var hosts = Array.prototype.slice.call(root.querySelectorAll('[data-expert-lazy-template]'));
            if (hosts.length === 0) {
                return;
            }
            hosts.sort(function (a, b) {
                return Number(a.getAttribute('data-expert-lazy-order') || 0) - Number(b.getAttribute('data-expert-lazy-order') || 0);
            });

            function mountFromTemplate(host) {
                var tid = host.getAttribute('data-expert-lazy-template');
                if (!tid) {
                    return false;
                }
                var tpl = document.getElementById(tid);
                if (!tpl || !tpl.content) {
                    return false;
                }
                var inner = tpl.content.firstElementChild;
                if (!inner) {
                    return false;
                }
                host.replaceWith(inner);
                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    try {
                        window.Alpine.initTree(inner);
                    } catch (e) {}
                }
                inner.removeAttribute('aria-busy');
                document.dispatchEvent(new CustomEvent('rentbase:tenant-dom-mounted', { detail: { root: inner } }));
                return true;
            }

            function scrollToFragmentId(id) {
                var el = document.getElementById(id);
                if (el && typeof el.scrollIntoView === 'function') {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            function mountLazyAnchorIfNeeded(id) {
                if (!id) {
                    return false;
                }
                if (document.getElementById(id)) {
                    return true;
                }
                var host = root.querySelector('[data-expert-lazy-anchor="' + id + '"]');
                if (!host || !host.isConnected) {
                    return false;
                }
                return mountFromTemplate(host);
            }

            function syncLocationHashToLazySection() {
                var h = window.location.hash;
                if (!h || h.length < 2) {
                    return;
                }
                var id = decodeURIComponent(h.slice(1));
                if (!id) {
                    return;
                }
                if (document.getElementById(id)) {
                    return;
                }
                if (!mountLazyAnchorIfNeeded(id)) {
                    return;
                }
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        scrollToFragmentId(id);
                    });
                });
            }

            document.addEventListener(
                'click',
                function (e) {
                    var t = e.target;
                    if (!t || typeof t.closest !== 'function') {
                        return;
                    }
                    var a = t.closest('a[href]');
                    if (!a) {
                        return;
                    }
                    var raw = a.getAttribute('href');
                    if (!raw || raw.indexOf('#') === -1) {
                        return;
                    }
                    var u;
                    try {
                        u = new URL(raw, window.location.href);
                    } catch (err) {
                        return;
                    }
                    if (u.origin !== window.location.origin) {
                        return;
                    }
                    if (u.pathname !== window.location.pathname) {
                        return;
                    }
                    if (!u.hash || u.hash.length < 2) {
                        return;
                    }
                    var id = decodeURIComponent(u.hash.slice(1));
                    if (!id || document.getElementById(id)) {
                        return;
                    }
                    var host = root.querySelector('[data-expert-lazy-anchor="' + id + '"]');
                    if (!host || !host.isConnected) {
                        return;
                    }
                    e.preventDefault();
                    mountFromTemplate(host);
                    if (window.history && window.history.pushState) {
                        window.history.pushState(null, '', u.pathname + u.search + u.hash);
                    } else {
                        window.location.hash = u.hash;
                    }
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            scrollToFragmentId(id);
                        });
                    });
                },
                true
            );

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncLocationHashToLazySection);
            } else {
                syncLocationHashToLazySection();
            }
            window.addEventListener('hashchange', syncLocationHashToLazySection);

            var next = 0;
            function observeNext() {
                if (next >= hosts.length) {
                    return;
                }
                var host = hosts[next];
                if (!host.isConnected) {
                    next++;
                    observeNext();
                    return;
                }
                var io = new IntersectionObserver(
                    function (entries, obs) {
                        if (!entries[0] || !entries[0].isIntersecting) {
                            return;
                        }
                        obs.disconnect();
                        mountFromTemplate(host);
                        next++;
                        observeNext();
                    },
                    { root: null, rootMargin: '220px 0px 320px 0px', threshold: 0.01 }
                );
                io.observe(host);
            }
            observeNext();
        })();
    </script>
@endpush
