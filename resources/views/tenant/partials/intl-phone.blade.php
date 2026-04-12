{{-- Единый скрипт для CDN и тестов (withoutVite); синхронизировать с resources/js/tenant-intl-phone.js.
     Публичные поля: data-rb-intl-phone + attachPublicTelField; после вставки HTML — CustomEvent rentbase:tenant-dom-mounted (detail.root). --}}
@php($__rbIntlPhoneInline = file_get_contents(resource_path('js/tenant-intl-phone.js')))
<script><?php echo $__rbIntlPhoneInline; ?></script>
