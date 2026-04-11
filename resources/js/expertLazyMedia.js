/**
 * Видео expert_auto: не тянуть MP4 с CDN, пока блок не у окна (меньше запросов и TTFB до первого кадра).
 * Диалоги: см. data-expert-dialog-src + скрипт в expert_hero (открытие модалки).
 */
function hydrateLazyInlineVideo(video) {
    const url = video.getAttribute('data-expert-lazy-src');
    if (!url || video.getAttribute('src')) {
        return;
    }
    video.setAttribute('src', url);
    video.removeAttribute('data-expert-lazy-src');
}

export function bootExpertLazyInlineVideos() {
    const nodes = document.querySelectorAll('video[data-expert-lazy-src]');
    if (nodes.length === 0) {
        return;
    }
    if (typeof IntersectionObserver === 'undefined') {
        nodes.forEach((v) => hydrateLazyInlineVideo(v));

        return;
    }
    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((ent) => {
                if (!ent.isIntersecting) {
                    return;
                }
                const v = ent.target;
                if (v instanceof HTMLVideoElement) {
                    hydrateLazyInlineVideo(v);
                }
                io.unobserve(ent.target);
            });
        },
        { root: null, rootMargin: '120px 0px', threshold: 0.01 },
    );
    nodes.forEach((v) => io.observe(v));
}

if (typeof document !== 'undefined') {
    const run = () => bootExpertLazyInlineVideos();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    document.addEventListener('rentbase:tenant-dom-mounted', run);
}
