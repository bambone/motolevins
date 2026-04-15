/**
 * Tenant public keyboard contract: focusable discovery + Tab цикл внутри контейнера (modal / mobile nav).
 * Не перехватывает Tab, когда фокус во вспомогательном оверлее (например Flatpickr в document.body).
 */

const FOCUSABLE_SELECTOR = [
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled]):not([tabindex="-1"])',
    'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
    'select:not([disabled]):not([tabindex="-1"])',
    'textarea:not([disabled]):not([tabindex="-1"])',
    'summary',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function isVisible(el) {
    if (!el || !(el instanceof Element)) {
        return false;
    }
    if (el.closest('[hidden], [aria-hidden="true"]')) {
        return false;
    }
    let cur = el;
    while (cur && cur !== document.documentElement) {
        const st = window.getComputedStyle(cur);
        if (st.visibility === 'hidden' || st.display === 'none') {
            return false;
        }
        cur = cur.parentElement;
    }

    return el.getClientRects().length > 0;
}

export function collectFocusables(container) {
    if (!container) {
        return [];
    }
    const nodes = Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR));

    return nodes.filter((el) => isVisible(el));
}

export function firstFocusable(container) {
    const list = collectFocusables(container);

    return list[0] || null;
}

export function isExternalOverlayFocused() {
    const el = document.activeElement;
    if (!el || typeof el.closest !== 'function') {
        return false;
    }

    return Boolean(el.closest('.flatpickr-calendar, .flatpickr-monthSelect'));
}

/**
 * @param {HTMLElement} container
 * @param {KeyboardEvent} event
 */
export function trapTabWithin(container, event) {
    if (!container || event.key !== 'Tab' || event.defaultPrevented) {
        return;
    }
    if (isExternalOverlayFocused()) {
        return;
    }
    const list = collectFocusables(container);
    if (list.length === 0) {
        event.preventDefault();

        return;
    }
    const first = list[0];
    const last = list[list.length - 1];
    const active = document.activeElement;
    if (event.shiftKey) {
        if (active === first || !container.contains(active)) {
            event.preventDefault();
            last.focus();
        }
    } else if (active === last || !container.contains(active)) {
        event.preventDefault();
        first.focus();
    }
}

const api = {
    collectFocusables,
    firstFocusable,
    trapTabWithin,
    isExternalOverlayFocused,
};

if (typeof window !== 'undefined') {
    window.RentBaseTenantA11y = api;
}

export default api;
