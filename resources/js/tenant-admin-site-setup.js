/**
 * Guided setup: highlight [data-setup-target] / fallbacks for active session (payload in #tenant-site-setup-payload).
 */
function isSetupElementVisible(el) {
    if (!el || !(el instanceof Element)) {
        return false;
    }
    const style = window.getComputedStyle(el);
    if (style.visibility === 'hidden' || style.display === 'none') {
        return false;
    }
    const r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
}

/**
 * @param {string} selector
 * @returns {Element|null}
 */
function firstVisibleMatch(selector) {
    const list = document.querySelectorAll(selector);
    for (let i = 0; i < list.length; i += 1) {
        const el = list[i];
        if (isSetupElementVisible(el)) {
            return el;
        }
    }
    return null;
}

/**
 * @param {string} attr
 * @param {string} value
 * @returns {Element|null}
 */
function firstVisibleByDataAttr(attr, value) {
    const esc =
        typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
            ? CSS.escape(value)
            : value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    return firstVisibleMatch(`[${attr}="${esc}"]`);
}

/**
 * @param {Record<string, unknown>} payload
 * @returns {Element|null}
 */
function resolveSetupHighlightTarget(payload) {
    const primary = payload.target_key;
    if (!primary || typeof primary !== 'string') {
        return null;
    }

    const fallbackKeys = Array.isArray(payload.target_fallback_keys) ? payload.target_fallback_keys : [];
    const keysToTry = [primary, ...fallbackKeys.filter((k) => typeof k === 'string' && k.length > 0)];

    for (let i = 0; i < keysToTry.length; i += 1) {
        const key = keysToTry[i];
        const esc =
            typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                ? CSS.escape(key)
                : key.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        const matches = document.querySelectorAll(`[data-setup-target="${esc}"]`);
        for (let j = 0; j < matches.length; j += 1) {
            const el = matches[j];
            if (isSetupElementVisible(el)) {
                return el;
            }
        }
    }

    const sectionTypes = Array.isArray(payload.page_builder_fallback_section_types)
        ? payload.page_builder_fallback_section_types
        : [];
    for (let s = 0; s < sectionTypes.length; s += 1) {
        const id = sectionTypes[s];
        if (typeof id !== 'string' || id === '') {
            continue;
        }
        const inCatalog = firstVisibleByDataAttr('data-setup-section-type', id);
        if (inCatalog) {
            return inCatalog;
        }
    }

    const action = payload.fallback_setup_action;
    if (typeof action === 'string' && action !== '') {
        const byAction = firstVisibleByDataAttr('data-setup-action', action);
        if (byAction) {
            return byAction;
        }
    }

    return null;
}

function clearTenantSiteSetupHighlights() {
    document.querySelectorAll('[data-setup-highlighted="1"]').forEach((el) => {
        el.classList.remove('fi-ts-setup-highlight');
        el.removeAttribute('data-setup-highlighted');
    });
}

function initTenantSiteSetup() {
    const payloadEl = document.getElementById('tenant-site-setup-payload');
    if (!payloadEl) {
        document.body.classList.remove('fi-ts-setup-active');
        clearTenantSiteSetupHighlights();
        document.body.style.paddingTop = '';
        return;
    }

    let payload;
    try {
        payload = JSON.parse(payloadEl.textContent || '{}');
    } catch {
        return;
    }

    document.body.classList.add('fi-ts-setup-active');

    clearTenantSiteSetupHighlights();

    const target = resolveSetupHighlightTarget(payload);
    if (!target) {
        const key = payload.target_key;
        if (key && window.console && console.info) {
            console.info('[tenant-site-setup] target not found', key);
        }
        const barOnly = document.getElementById('tenant-site-setup-bar');
        const topOffset = barOnly ? parseFloat(getComputedStyle(barOnly).top) || 0 : 0;
        const barH = barOnly ? barOnly.offsetHeight : 0;
        if (topOffset + barH > 0) {
            document.body.style.paddingTop = `${topOffset + barH}px`;
        }
        return;
    }

    target.classList.add('fi-ts-setup-highlight');
    target.setAttribute('data-setup-highlighted', '1');

    const bar = document.getElementById('tenant-site-setup-bar');
    const barH = bar ? bar.offsetHeight : 0;
    const topOffset = bar ? parseFloat(getComputedStyle(bar).top) || 0 : 0;
    if (topOffset + barH > 0) {
        document.body.style.paddingTop = `${topOffset + barH}px`;
    }
    const top = target.getBoundingClientRect().top + window.scrollY - topOffset - barH - 12;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTenantSiteSetup);
} else {
    initTenantSiteSetup();
}

document.addEventListener('livewire:navigated', initTenantSiteSetup);
