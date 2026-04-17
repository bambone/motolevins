<x-filament-panels::page>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        <span class="font-medium">Доступ</span> — можно ли пользоваться разделом по тарифу/оверрайду и каналу OneSignal на платформе.
        <span class="font-medium">Push</span> и <span class="font-medium">PWA</span> — включены ли переключатели «отправка push» / «динамический manifest» в кабинете клиента (при доступе: да / выкл; без доступа — —).
        Тариф: <span class="font-medium">Платформа → Тарифы</span>; оверрайд и коммерция: <span class="font-medium">Клиенты → клиент → «Push и PWA (платформа)»</span>.
    </p>
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 font-medium">Клиент</th>
                    <th class="px-3 py-2 font-medium">План</th>
                    <th class="px-3 py-2 font-medium">Override</th>
                    <th class="px-3 py-2 font-medium">Доступ</th>
                    <th class="px-3 py-2 font-medium">Провайдер</th>
                    <th class="px-3 py-2 font-medium">Подписки (CRM)</th>
                    <th class="px-3 py-2 font-medium">Push</th>
                    <th class="px-3 py-2 font-medium">PWA</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($this->tenants as $tenant)
                    @php($pushView = \App\TenantPush\TenantPushSettingsView::make($tenant, app(\App\TenantPush\TenantPushFeatureGate::class), app(\App\TenantPush\TenantPushCrmRequestRecipientResolver::class)))
                    @php
                        $entitled = $pushView->gate->isFeatureEntitled();
                        $subAgg = $pushView->subscriptionAggregate->value;
                        $s = $pushView->settings;
                        $pushCell = $entitled ? ($s->is_push_enabled ? 'да' : 'выкл') : '—';
                        $pwaCell = $entitled ? ($s->is_pwa_enabled ? 'да' : 'выкл') : '—';
                    @endphp
                    <tr>
                        <td class="px-3 py-2">
                            <a href="{{ \App\Filament\Platform\Resources\TenantResource::getUrl('edit', ['record' => $tenant]) }}" class="text-primary-600 hover:underline">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-3 py-2">{{ $tenant->plan?->slug ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $s->push_override ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entitled ? 'да' : 'нет' }}</td>
                        <td class="px-3 py-2">{{ $s->provider_status ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $subAgg }}</td>
                        <td class="px-3 py-2">{{ $pushCell }}</td>
                        <td class="px-3 py-2">{{ $pwaCell }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
