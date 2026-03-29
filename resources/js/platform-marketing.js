const pmIsLowPerfDevice = () =>
    window.innerWidth < 768 || window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (pmIsLowPerfDevice()) {
    document.documentElement.classList.add('reduced-motion');
}

/**
 * Плавный скролл для внутренних якорей с учётом фиксированного header.
 */
const headerOffset = () => {
    const header = document.querySelector('[data-pm-header]');
    return header ? header.getBoundingClientRect().height + 8 : 72;
};

const initPmAnchorScroll = () => {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        const id = anchor.getAttribute('href')?.slice(1);
        if (!id) {
            return;
        }
        anchor.addEventListener('click', (e) => {
            const target = document.getElementById(id);
            if (!target) {
                return;
            }
            e.preventDefault();
            const top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
            window.scrollTo({ top, behavior: 'smooth' });
            history.pushState(null, '', `#${id}`);
        });
    });
};

/**
 * Мобильное меню шапки (< lg): клавиатура, ресайз, закрытие по ссылке.
 */
const initPmMobileNav = () => {
    const toggle = document.querySelector('[data-pm-nav-toggle]');
    const panel = document.querySelector('[data-pm-mobile-menu]');
    if (!toggle || !panel) {
        return;
    }

    const iconOpen = toggle.querySelector('[data-pm-nav-icon-open]');
    const iconClose = toggle.querySelector('[data-pm-nav-icon-close]');

    const setOpen = (open) => {
        panel.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
        if (iconOpen && iconClose) {
            iconOpen.classList.toggle('hidden', open);
            iconClose.classList.toggle('hidden', !open);
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.classList.contains('hidden'));
    });

    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
            setOpen(false);
        }
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
        if (e.matches) {
            setOpen(false);
        }
    });
};

const initPmScrollReveal = () => {
    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -50px 0px',
        threshold: 0.1
    };

    const observers = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const useMotion = !pmIsLowPerfDevice();
    document.querySelectorAll('.fade-reveal').forEach((el) => {
        if (!useMotion) {
            el.style.transitionDelay = '0ms';
            el.classList.add('visible');
        } else {
            observers.observe(el);
        }
    });
};

/**
 * Хуки аналитики: CustomEvent `pm:analytics` + window.pmTrack для GA4 / Метрики и т.д.
 * detail: { name, location?, cta?, tier?, case?, intent?, depth?, faqIndex? }
 */
const pmDispatch = (name, detail = {}) => {
    const payload = { name, ...detail };
    document.dispatchEvent(new CustomEvent('pm:analytics', { detail: payload }));
    if (typeof window.pmTrack === 'function') {
        window.pmTrack(name, detail);
    }
};

const pmEmitGrowthAliases = (eventName, detail) => {
    if (eventName === 'cta_click' && detail.location === 'hero') {
        pmDispatch('hero_cta_click', detail);
    }
    if (eventName === 'cta_click' && typeof detail.location === 'string' && detail.location.startsWith('pricing')) {
        pmDispatch('pricing_select', detail);
    }
    if (eventName === 'cta_click' && detail.cta === 'secondary') {
        const href = detail.href || '';
        if (href.includes('intent=demo') || href.includes('intent%3Ddemo')) {
            pmDispatch('demo_open', detail);
        }
    }
};

const initPmAnalyticsClicks = () => {
    document.body.addEventListener('click', (e) => {
        const el = e.target.closest('[data-pm-event]');
        if (!el) {
            return;
        }
        const eventName = el.getAttribute('data-pm-event');
        if (!eventName) {
            return;
        }
        const detail = {
            location: el.getAttribute('data-pm-location') || undefined,
            cta: el.getAttribute('data-pm-cta') || undefined,
            tier: el.getAttribute('data-pm-tier') || undefined,
            case: el.getAttribute('data-pm-case') || undefined,
            href: el.getAttribute('href') || undefined,
        };
        pmDispatch(eventName, detail);
        pmEmitGrowthAliases(eventName, detail);
    });
};

const initPmContactSuccess = () => {
    const box = document.querySelector('[data-pm-contact-success="1"]');
    if (!box) {
        return;
    }
    const intent = box.getAttribute('data-pm-contact-intent') || undefined;
    pmDispatch('contact_form_success', { intent });
    pmDispatch('contact_success', { intent });
};

const initPmScrollDepth = () => {
    const milestones = [25, 50, 75, 90];
    const hit = new Set();
    let scheduled = false;

    const check = () => {
        const doc = document.documentElement;
        const scrollTop = window.scrollY || doc.scrollTop;
        const max = doc.scrollHeight - window.innerHeight;
        if (max <= 0) {
            return;
        }
        const pct = Math.round((scrollTop / max) * 100);
        milestones.forEach((m) => {
            if (pct >= m && !hit.has(m)) {
                hit.add(m);
                pmDispatch('scroll_depth', { depth: m });
            }
        });
    };

    window.addEventListener(
        'scroll',
        () => {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(() => {
                scheduled = false;
                check();
            });
        },
        { passive: true }
    );
};

const initPmFaqDetails = () => {
    document.querySelectorAll('details[data-pm-faq-index]').forEach((det) => {
        det.addEventListener('toggle', () => {
            if (!det.open) {
                return;
            }
            pmDispatch('faq_expand', {
                faqIndex: det.getAttribute('data-pm-faq-index') || undefined,
            });
        });
    });
};

const initPlatformMarketing = () => {
    initPmAnchorScroll();
    initPmMobileNav();
    initPmScrollReveal();
    initPmAnalyticsClicks();
    initPmContactSuccess();
    initPmScrollDepth();
    initPmFaqDetails();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlatformMarketing);
} else {
    initPlatformMarketing();
}
