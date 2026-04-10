{{-- Как в конструкторе Метрики: после загрузчика — отдельная строка ym(ID, "init", …) с литералом номера (иначе ?_ym_status-check часто не находит счётчик). --}}
@php
    $cid = (int) $counterId;
    $tagSrc = 'https://mc.yandex.ru/metrika/tag.js?id='.$cid;
    $ymInit = [
        'clickmap' => (bool) $clickmap,
        'trackLinks' => (bool) $trackLinks,
        'accurateTrackBounce' => (bool) $accurateTrackBounce,
        'webvisor' => (bool) $webvisor,
    ];
@endphp
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, 'script', <?= json_encode($tagSrc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, 'ym');
    ym(<?= $cid ?>, 'init', Object.assign(<?= json_encode($ymInit, JSON_UNESCAPED_UNICODE) ?>, {
        referrer: document.referrer,
        url: location.href
    }));
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/{{ $cid }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
