{{-- Единый скрипт для CDN и тестов (withoutVite); синхронизировать с resources/js/tenant-intl-phone.js --}}
<script>
@php echo file_get_contents(resource_path('js/tenant-intl-phone.js')); @endphp
</script>
