/**
 * Публичная отправка отзывов: POST /api/tenant/reviews/submit (AF-014).
 */

function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') || '' : '';
}

function clearReviewFieldErrors(form) {
    form.querySelectorAll('[data-rb-public-field]').forEach((wrap) => {
        wrap.querySelectorAll('.rb-review-field-error').forEach((n) => n.remove());
        wrap.querySelectorAll('input, textarea, select').forEach((el) => {
            el.classList.remove('ring-2', 'ring-red-500/40', 'border-red-500/50');
        });
    });
}

function setReviewFieldError(form, fieldName, message) {
    const wrap = form.querySelector(`[data-rb-public-field="${fieldName}"]`);
    if (!wrap) {
        return;
    }
    const control = wrap.querySelector('input, textarea, select');
    if (control) {
        control.classList.add('ring-2', 'ring-red-500/40', 'border-red-500/50');
    }
    const p = document.createElement('p');
    p.className = 'rb-review-field-error mt-1.5 text-[13px] leading-snug text-red-200';
    p.setAttribute('role', 'alert');
    p.textContent = message;
    wrap.appendChild(p);
}

function showReviewAlert(root, message) {
    const el = root.querySelector('[data-rb-review-alert]');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden');
}

function hideReviewAlert(root) {
    const el = root.querySelector('[data-rb-review-alert]');
    if (!el) {
        return;
    }
    el.classList.add('hidden');
    el.textContent = '';
}

function showReviewSuccess(root, message) {
    const el = root.querySelector('[data-rb-review-success]');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden');
}

function hideReviewSuccess(root) {
    const el = root.querySelector('[data-rb-review-success]');
    if (!el) {
        return;
    }
    el.classList.add('hidden');
    el.textContent = '';
}

async function submitReviewForm(form) {
    const root = form.closest('[data-rb-review-submit-root]');
    const endpoint = form.getAttribute('data-rb-review-endpoint') || '';
    if (!endpoint || !root) {
        return;
    }

    const sendBtn = form.querySelector('[data-rb-review-send]');
    const alertBox = root.querySelector('[data-rb-review-alert]');
    const successBox = root.querySelector('[data-rb-review-success]');

    clearReviewFieldErrors(form);
    hideReviewAlert(root);
    hideReviewSuccess(root);

    const fd = new FormData(form);
    const token = csrfToken();
    if (sendBtn) {
        sendBtn.disabled = true;
    }

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body: fd,
        });

        const data = await res.json().catch(() => ({}));

        if (res.status === 422 && data.errors && typeof data.errors === 'object') {
            const errs = data.errors;
            Object.keys(errs).forEach((key) => {
                const msg = Array.isArray(errs[key]) ? errs[key][0] : String(errs[key]);
                setReviewFieldError(form, key, msg);
            });
            const first = form.querySelector('.rb-review-field-error');
            if (first && typeof first.scrollIntoView === 'function') {
                first.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
            return;
        }

        if (!res.ok) {
            const msg =
                typeof data.message === 'string' && data.message
                    ? data.message
                    : res.status === 429
                      ? 'Слишком много отправок. Подождите минуту и попробуйте снова.'
                      : 'Не удалось отправить отзыв. Попробуйте позже.';
            showReviewAlert(root, msg);
            return;
        }

        if (data.success && typeof data.message === 'string' && data.message) {
            form.reset();
            if (successBox) {
                successBox.textContent = data.message;
                successBox.classList.remove('hidden');
            }
            if (alertBox) {
                alertBox.classList.add('hidden');
            }
            form.querySelectorAll('input, textarea, select').forEach((el) => {
                el.classList.remove('ring-2', 'ring-red-500/40', 'border-red-500/50');
            });
        }
    } catch {
        showReviewAlert(root, 'Ошибка сети. Проверьте подключение и попробуйте снова.');
    } finally {
        if (sendBtn) {
            sendBtn.disabled = false;
        }
    }
}

function bindReviewSubmitForms() {
    document.querySelectorAll('form[data-rb-review-submit-form]').forEach((form) => {
        if (form.getAttribute('data-rb-review-bound') === '1') {
            return;
        }
        form.setAttribute('data-rb-review-bound', '1');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitReviewForm(form);
        });
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindReviewSubmitForms);
    } else {
        bindReviewSubmitForms();
    }
}
