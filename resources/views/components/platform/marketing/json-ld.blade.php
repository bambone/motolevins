@props([
    'graph' => [],
])
@php
    $payload = count($graph) === 1 ? $graph[0] : ['@context' => 'https://schema.org', '@graph' => $graph];
@endphp
<script type="application/ld+json">{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
