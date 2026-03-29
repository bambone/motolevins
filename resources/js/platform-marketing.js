/**
 * Плавный скролл для внутренних якорей с учётом фиксированного header.
 */
const headerOffset = () => {
    const header = document.querySelector('[data-pm-header]');
    return header ? header.getBoundingClientRect().height + 8 : 72;
};

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
