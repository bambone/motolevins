{{-- Progressive enhancement: html.js + inline «Читать полностью» для @see tenant.components.review-quote-and-expand --}}
@once('tenant-review-expand-script')
    <script>
        (function () {
            document.documentElement.classList.add('js');

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-review-toggle]');
                if (!btn) return;

                var id = btn.getAttribute('aria-controls');
                var body = id ? document.getElementById(id) : null;

                if (!body) {
                    var card = btn.closest('[data-review-card]');
                    body = card ? card.querySelector('[data-review-body]') : null;
                }

                if (!body) return;

                var collapsed = body.getAttribute('data-collapsed') === 'true';

                body.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
                btn.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
                btn.textContent = collapsed ? 'Свернуть' : 'Читать полностью';
            });
        })();
    </script>
@endonce
