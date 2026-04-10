@php
    use App\View\SchedulingCalendarIntegrationsBanner;

    $tenant = currentTenant();
@endphp
@if (SchedulingCalendarIntegrationsBanner::shouldShow($tenant, request()->path()))
    <div class="fi-alert border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100" role="status">
        <strong class="font-medium">Календарные интеграции выключены</strong>
        <span class="mt-1 block text-amber-900/90 dark:text-amber-100/90">
            Запись и слоты работают; подключение Google / CalDAV и запись событий наружу доступны при включении интеграций для клиента и настройке подключений.
        </span>
    </div>
@endif
