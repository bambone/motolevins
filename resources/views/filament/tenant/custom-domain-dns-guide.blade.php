@php
    /** @var array $resolution */
    /** @var int|string $recordId */
    /** @var string $verificationPrefix */
    /** @var string $verificationToken */
    /** @var string $serverIp */
    /** @var array<string, string> $registrarOptions */
    /** @var array<string, string> $ruCenterVariantOptions */
    /** @var array $registrarGuide */
    /** @var string $guideKeyRuCenter */
    /** @var string $dnsRegistrarGuideKey */
    /** @var bool $hasVerificationToken */
    $selectId = 'dns-registrar-guide-'.$recordId;
    $variantSelectId = 'dns-registrar-variant-'.$recordId;
@endphp

<div class="space-y-6 text-sm text-gray-950 dark:text-gray-100">
    <div>
        <label for="{{ $selectId }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
            Где у вас управляется DNS
        </label>
        <select
            id="{{ $selectId }}"
            name="dns_registrar_guide_key"
            wire:model.live="dnsRegistrarGuideKey"
            class="fi-select-input block w-full max-w-xl rounded-lg border border-gray-950/10 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:bg-gray-50 disabled:text-gray-500 disabled:opacity-70 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-400 dark:disabled:bg-transparent dark:disabled:text-gray-400"
        >
            @foreach ($registrarOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-2 max-w-xl text-xs text-gray-600 dark:text-gray-400">
            Только подбирает текст инструкции на этой странице. Не сохраняется и не влияет на DNS — главное действие: кнопка «Проверить и подключить» вверху.
        </p>
    </div>

    @if (($dnsRegistrarGuideKey ?? '') === $guideKeyRuCenter && count($ruCenterVariantOptions) > 0)
        <div>
            <label for="{{ $variantSelectId }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                Вариант в RU-CENTER
            </label>
            <select
                id="{{ $variantSelectId }}"
                name="dns_registrar_guide_variant_key"
                wire:model.live="dnsRegistrarGuideVariantKey"
                class="fi-select-input block w-full max-w-xl rounded-lg border border-gray-950/10 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:bg-gray-50 disabled:text-gray-500 disabled:opacity-70 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-400 dark:disabled:bg-transparent dark:disabled:text-gray-400"
            >
                @foreach ($ruCenterVariantOptions as $vValue => $vLabel)
                    <option value="{{ $vValue }}">{{ $vLabel }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($resolution['displayHost'] !== $resolution['dnsHost'])
        <p class="text-xs text-gray-600 dark:text-gray-400">
            Домен в панели (Unicode): <span class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ $resolution['displayHost'] }}</span>
            · для DNS используйте: <span class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ $resolution['dnsHost'] }}</span>
        </p>
    @endif

    {{-- Зона 1: записи --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900/40">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-50">Что нужно добавить в DNS</h3>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
            Значения ниже индивидуальны для вашего домена — скопируйте их в панель регистратора без изменений.
        </p>
        @if (! ($hasVerificationToken ?? false))
            <div
                class="mt-3 rounded-lg border border-red-500/35 bg-red-500/10 p-3 text-xs text-red-950 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100"
                role="alert"
            >
                <p class="font-semibold">Нет кода верификации TXT</p>
                <p class="mt-1 opacity-95">
                    Для этой записи не сгенерирован токен. Обновите страницу или обратитесь в поддержку — без значения TXT проверка домена может не пройти.
                </p>
            </div>
        @endif
        @if (! empty($resolution['subdomainDnsNote']))
            <p
                class="mt-3 rounded-lg border border-sky-500/30 bg-sky-500/10 p-3 text-xs text-sky-950 dark:border-sky-400/30 dark:bg-sky-500/10 dark:text-sky-100"
                data-dns-guide="subdomain-context"
            >
                {{ $resolution['subdomainDnsNote'] }}
            </p>
        @endif
        <div class="mt-4 overflow-x-auto">
            <table class="fi-ta-table w-full min-w-[20rem] border-collapse text-left text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="py-2 pe-3 font-semibold text-gray-700 dark:text-gray-300">Тип</th>
                        <th class="py-2 pe-3 font-semibold text-gray-700 dark:text-gray-300">Имя (host)</th>
                        <th class="py-2 font-semibold text-gray-700 dark:text-gray-300">Значение</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    <tr>
                        <td class="py-2 pe-3 align-top font-medium">TXT</td>
                        <td class="py-2 pe-3 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">{{ $verificationPrefix }}</td>
                        <td class="py-2 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">
                            @if ($hasVerificationToken ?? false)
                                {{ $verificationToken }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 pe-3 align-top font-medium">A</td>
                        <td class="py-2 pe-3 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">{{ $resolution['aRecordNameHint'] }}</td>
                        <td class="py-2 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">{{ $serverIp }}</td>
                    </tr>
                    @if ($resolution['showWwwCnameRow'])
                        <tr data-dns-cname-www>
                            <td class="py-2 pe-3 align-top font-medium">CNAME</td>
                            <td class="py-2 pe-3 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">www</td>
                            <td class="py-2 align-top font-mono text-xs break-all text-gray-900 dark:text-gray-100">{{ $resolution['dnsHost'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Зона 2: важные замечания --}}
    <div class="rounded-xl border border-amber-500/25 bg-amber-500/5 p-4 dark:border-amber-400/20 dark:bg-amber-500/10">
        <h3 class="text-base font-semibold text-amber-950 dark:text-amber-100">Важные замечания</h3>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-amber-950/90 dark:text-amber-50/90 sm:text-sm">
            <li>Нужно не переносить домен к другому регистратору, а изменить DNS-записи, чтобы сайт открывался с нашего сервера.</li>
            <li>Если у домена указаны внешние DNS-серверы (NS), записи меняются там, где реально обслуживается зона, а не обязательно у регистратора.</li>
            <li>Если на домене уже работает почта, не удаляйте MX и прочие почтовые записи без необходимости.</li>
            <li>Для одного и того же имени нельзя одновременно держать A и CNAME.</li>
            <li><strong class="font-semibold">Если для «собаки» (@) или www уже есть старые записи (включая старый IP), замените их на новые из таблицы выше — не добавляйте второй набор.</strong></li>
        </ul>
    </div>

    {{-- Зона 3: регистратор --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900/40">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-50">{{ $registrarGuide['title'] }}</h3>
        @if (! empty($registrarGuide['intro']))
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 sm:text-sm">{{ $registrarGuide['intro'] }}</p>
        @endif

        @if (($registrarGuide['requiresExternalDnsWarning'] ?? false) === true)
            <p class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-800 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                Если DNS-зона обслуживается не у этого провайдера (другие NS), шаги ниже могут не подойти — внесите те же записи из таблицы там, где сейчас редактируется зона.
            </p>
        @endif

        @if (count($registrarGuide['steps']) > 0)
            <ol class="mt-3 list-decimal space-y-2 ps-4 text-xs text-gray-800 dark:text-gray-200 sm:text-sm">
                @foreach ($registrarGuide['steps'] as $step)
                    <li>
                        {{ $step['text'] ?? '' }}
                        @if (! empty($step['emphasis']))
                            <span class="font-medium text-primary-600 dark:text-primary-400">{{ $step['emphasis'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        @if (count($registrarGuide['notes']) > 0)
            <ul class="mt-3 list-inside list-disc space-y-1 text-xs text-gray-600 dark:text-gray-400">
                @foreach ($registrarGuide['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        @endif

        @if (! empty($registrarGuide['helpUrl']) && ! empty($registrarGuide['helpLabel']))
            <p class="mt-4">
                <a
                    href="{{ $registrarGuide['helpUrl'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-primary-500/40 bg-primary-500/10 px-3 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-500/15 dark:border-primary-400/40 dark:bg-primary-500/15 dark:text-primary-200 dark:hover:bg-primary-500/25"
                >
                    {{ $registrarGuide['helpLabel'] }}
                    <span class="text-xs font-normal opacity-80" aria-hidden="true">↗</span>
                </a>
            </p>
        @endif

        <p class="mt-4 border-s-4 border-primary-500 ps-3 text-sm leading-snug text-gray-700 dark:border-primary-400 dark:text-gray-300">
            После обновления DNS вернитесь на эту страницу и нажмите вверху кнопку «Проверить и подключить».
        </p>
    </div>
</div>
