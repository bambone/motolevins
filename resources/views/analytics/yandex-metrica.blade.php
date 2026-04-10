{{-- Формат близок к конструктору Метрики: tag.js?id=…, noscript watch, init с referrer/url (проверка ?_ym_status-check=…). --}}
@php
    $cid = (int) $counterId;
    $ymInlineConfig = [
        'tagSrc' => 'https://mc.yandex.ru/metrika/tag.js?id='.$cid,
        'counterId' => $cid,
        'init' => [
            'clickmap' => (bool) $clickmap,
            'trackLinks' => (bool) $trackLinks,
            'accurateTrackBounce' => (bool) $accurateTrackBounce,
            'webvisor' => (bool) $webvisor,
        ],
    ];
    $ymInlineConfigJson = json_encode($ymInlineConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
@endphp
<script type="application/json" id="ym-metrica-inline-config"><?= $ymInlineConfigJson ?></script>
<script type="text/javascript">
    (function () {
        var cfg = JSON.parse(document.getElementById('ym-metrica-inline-config').textContent);
        var m = window, e = document, t = 'script', r = cfg.tagSrc, i = 'ym', k, a;
        m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
        m[i].l = 1 * new Date();
        for (var j = 0; j < document.scripts.length; j++) {
            if (document.scripts[j].src === r) {
                return;
            }
        }
        k = e.createElement(t);
        a = e.getElementsByTagName(t)[0];
        k.async = 1;
        k.src = r;
        a.parentNode.insertBefore(k, a);
        m[i](cfg.counterId, 'init', Object.assign(cfg.init, {
            referrer: document.referrer,
            url: location.href
        }));
    })();
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/{{ $cid }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
