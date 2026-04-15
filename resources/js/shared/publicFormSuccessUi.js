/**
 * Единый контракт success-state для публичных tenant-форм (AF-011).
 * Заголовок фиксированный; основной текст — с сервера или fallback.
 */

export const RB_PUBLIC_FORM_SUCCESS_TITLE = 'Спасибо!';

export const RB_PUBLIC_FORM_SUCCESS_LEAD_FALLBACK =
    'Мы получили ваше обращение и свяжемся с вами в ближайшее время.';

/**
 * @param {string|undefined|null} serverMessage
 * @param {string|undefined|null} defaultLead
 */
export function rbResolvePublicFormSuccessLead(serverMessage, defaultLead) {
    const m = String(serverMessage ?? '').trim();
    if (m !== '') {
        return m;
    }
    const d = String(defaultLead ?? '').trim();

    return d !== '' ? d : RB_PUBLIC_FORM_SUCCESS_LEAD_FALLBACK;
}

/**
 * @param {object} detail
 */
export function rbDispatchPublicFormSuccess(detail) {
    try {
        document.dispatchEvent(new CustomEvent('rentbase:public-form-success', { detail }));
    } catch {
        /* ignore */
    }
}

/**
 * @param {HTMLElement|null|undefined} el
 */
export function rbFocusPublicSuccessRoot(el) {
    if (!el || typeof el.focus !== 'function') {
        return;
    }
    try {
        el.focus({ preventScroll: true });
    } catch {
        try {
            el.focus();
        } catch {
            /* ignore */
        }
    }
}
