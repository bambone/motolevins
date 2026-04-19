<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

/**
 * Каталог пошаговых подсказок по DNS для разных регистраторов (кабинет клиента, свой домен).
 *
 * @phpstan-type GuideShape array{
 *     label: string,
 *     title: string,
 *     intro: string,
 *     steps: list<array{text: string, emphasis?: string}>,
 *     notes: list<string>,
 *     helpUrl: string|null,
 *     helpLabel: string|null,
 *     requiresExternalDnsWarning: bool,
 *     variants: array<string, array{
 *         label: string,
 *         intro?: string,
 *         steps?: list<array{text: string, emphasis?: string}>,
 *         notes?: list<string>,
 *     }>|null,
 * }
 */
final class CustomDomainDnsRegistrarGuide
{
    public const KEY_DEFAULT = '';

    public const KEY_REG_RU = 'reg_ru';

    public const KEY_RU_CENTER = 'ru_center';

    public const KEY_TIMEWEB = 'timeweb';

    public const KEY_BEGET = 'beget';

    public const KEY_HOSTER_BY = 'hoster_by';

    public const KEY_PS_KZ = 'ps_kz';

    public const KEY_NIC_UA = 'nic_ua';

    public const RU_CENTER_VARIANT_DNS_HOSTING = 'dns_hosting';

    public const RU_CENTER_VARIANT_DNS_PREMIUM = 'dns_premium';

    public static function defaultKey(): string
    {
        return self::KEY_DEFAULT;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::catalog() as $key => $entry) {
            $out[$key] = $entry['label'];
        }

        return $out;
    }

    /**
     * @return array<string, GuideShape>
     */
    public static function all(): array
    {
        return self::catalog();
    }

    /**
     * Нормализованный гайд для ключа (и варианта, если есть).
     *
     * @return GuideShape
     */
    public static function guide(?string $key, ?string $variantKey = null): array
    {
        $catalog = self::catalog();
        $key = $key ?? self::KEY_DEFAULT;

        if ($key !== self::KEY_DEFAULT && ! isset($catalog[$key])) {
            $key = self::KEY_DEFAULT;
        }

        /** @var GuideShape $base */
        $base = $catalog[$key];

        if ($key === self::KEY_RU_CENTER && is_array($base['variants']) && $base['variants'] !== []) {
            $variantKey = $variantKey !== null && isset($base['variants'][$variantKey])
                ? $variantKey
                : array_key_first($base['variants']);
            $variant = $base['variants'][$variantKey];
            if (($variant['intro'] ?? '') !== '') {
                $base['intro'] = $variant['intro'];
            }
            if (isset($variant['steps']) && $variant['steps'] !== []) {
                $base['steps'] = $variant['steps'];
            }
            $variantNotes = $variant['notes'] ?? [];
            if ($variantNotes !== []) {
                $base['notes'] = array_values(array_merge($base['notes'], $variantNotes));
            }
        }

        $base['variants'] = $key === self::KEY_RU_CENTER ? $base['variants'] : null;

        return $base;
    }

    /**
     * @return array<string, string>
     */
    public static function ruCenterVariantOptions(): array
    {
        $g = self::catalog()[self::KEY_RU_CENTER];
        if (! is_array($g['variants'])) {
            return [];
        }

        $labels = [];
        foreach ($g['variants'] as $vKey => $v) {
            $labels[$vKey] = $v['label'];
        }

        return $labels;
    }

    public static function defaultRuCenterVariantKey(): string
    {
        $keys = array_keys(self::ruCenterVariantOptions());

        return $keys[0] ?? self::RU_CENTER_VARIANT_DNS_HOSTING;
    }

    /**
     * @return array<string, GuideShape>
     */
    private static function catalog(): array
    {
        return [
            self::KEY_DEFAULT => [
                'label' => 'Не уверен / общая инструкция',
                'title' => 'Общая инструкция',
                'intro' => 'Откройте у своего регистратора или у сервиса, где сейчас обслуживается DNS-зона домена, раздел управления DNS-записями.',
                'steps' => [
                    ['text' => 'Войдите в личный кабинет компании, где зарегистрирован домен (или где настроены DNS-серверы для этого домена).'],
                    ['text' => 'Найдите раздел вроде «DNS», «Управление зоной», «Редактор DNS» или «DNS-хостинг» — названия зависят от поставщика.'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице (значения индивидуальны для вашего домена).'],
                    ['text' => 'Сохраните изменения и дождитесь их применения (см. подсказку вверху секции про время распространения DNS).'],
                ],
                'notes' => [],
                'helpUrl' => null,
                'helpLabel' => null,
                'requiresExternalDnsWarning' => false,
                'variants' => null,
            ],

            self::KEY_REG_RU => [
                'label' => 'REG.RU',
                'title' => 'Как это сделать в REG.RU',
                'intro' => 'В REG.RU записи добавляются в блоке управления зоной на стороне REG.RU.',
                'steps' => [
                    ['text' => 'Зайдите в личный кабинет REG.RU.'],
                    ['text' => 'Откройте раздел «Домены» и выберите нужный домен.'],
                    ['text' => 'В блоке «DNS-серверы и управление зоной» нажмите «Изменить» — там редактируются DNS-записи.'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Нажмите «Сохранить» или «Готово».'],
                ],
                'notes' => [
                    'Если для «собаки» (@) или www уже есть старые записи, замените их на новые из инструкции, а не добавляйте второй набор.',
                ],
                'helpUrl' => 'https://help.reg.ru/support/dns',
                'helpLabel' => 'Справка REG.RU по DNS',
                'requiresExternalDnsWarning' => false,
                'variants' => null,
            ],

            self::KEY_RU_CENTER => [
                'label' => 'RU-CENTER (nic.ru)',
                'title' => 'Как это сделать в RU-CENTER',
                'intro' => 'В RU-CENTER сценарий зависит от того, подключён ли DNS-хостинг или DNS-премиум.',
                'steps' => [],
                'notes' => [
                    'Менять записи напрямую в RU-CENTER можно, когда используется DNS-хостинг или DNS-премиум. Если этих услуг нет, зона может обслуживаться у другого провайдера — тогда правки нужно вносить там.',
                ],
                'helpUrl' => 'https://www.nic.ru/help/',
                'helpLabel' => 'Справка RU-CENTER',
                'requiresExternalDnsWarning' => true,
                'variants' => [
                    self::RU_CENTER_VARIANT_DNS_HOSTING => [
                        'label' => 'DNS-хостинг',
                        'intro' => 'При подключённом DNS-хостинге зона редактируется в соответствующем разделе панели.',
                        'steps' => [
                            ['text' => 'Войдите в личный кабинет RU-CENTER.'],
                            ['text' => 'Откройте «DNS-хостинг» → «Управление DNS-зонами» и выберите ваш домен.'],
                            ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                            ['text' => 'Сохраните изменения.'],
                        ],
                        'notes' => [
                            'Если для @ или www уже есть старые записи, замените их на новые, не дублируйте.',
                        ],
                    ],
                    self::RU_CENTER_VARIANT_DNS_PREMIUM => [
                        'label' => 'DNS-премиум',
                        'intro' => 'При DNS-премиум зона настраивается из карточки домена.',
                        'steps' => [
                            ['text' => 'Войдите в личный кабинет RU-CENTER.'],
                            ['text' => 'Откройте «Домены», выберите домен, затем «DNS-премиум» → «Панель управления».'],
                            ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                            ['text' => 'Сохраните изменения.'],
                        ],
                        'notes' => [
                            'Если для @ или www уже есть старые записи, замените их на новые, не дублируйте.',
                        ],
                    ],
                ],
            ],

            self::KEY_TIMEWEB => [
                'label' => 'Timeweb',
                'title' => 'Как это сделать в Timeweb',
                'intro' => 'В панели Timeweb записи задаются в редакторе DNS для домена.',
                'steps' => [
                    ['text' => 'Зайдите в панель Timeweb.'],
                    ['text' => 'Откройте «Домены и SSL» → «Мои домены» и выберите ваш домен.'],
                    ['text' => 'Перейдите на вкладку «Редактор DNS».'],
                    ['text' => 'Добавьте или измените записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Сохраните изменения.'],
                ],
                'notes' => [
                    'Если домен использует не NS Timeweb, записи нужно менять у текущего держателя NS, а не в панели Timeweb.',
                ],
                'helpUrl' => 'https://timeweb.com/docs/domains/dns-editor/',
                'helpLabel' => 'Документация Timeweb по DNS',
                'requiresExternalDnsWarning' => true,
                'variants' => null,
            ],

            self::KEY_BEGET => [
                'label' => 'Beget',
                'title' => 'Как это сделать в Beget',
                'intro' => 'В Beget зона редактируется в разделе DNS.',
                'steps' => [
                    ['text' => 'Зайдите в панель Beget.'],
                    ['text' => 'Откройте раздел «DNS».'],
                    ['text' => 'Выберите ваш домен и режим редактирования DNS-зоны.'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Сохраните изменения.'],
                ],
                'notes' => [
                    'Если домен обслуживается не на DNS Beget, сначала выясните, где сейчас находится DNS-зона.',
                ],
                'helpUrl' => 'https://beget.com/ru/kb/how-to-dns',
                'helpLabel' => 'Справка Beget',
                'requiresExternalDnsWarning' => true,
                'variants' => null,
            ],

            self::KEY_HOSTER_BY => [
                'label' => 'hoster.by',
                'title' => 'Как это сделать в hoster.by',
                'intro' => 'В hoster.by используйте расширенный редактор DNS для домена.',
                'steps' => [
                    ['text' => 'Зайдите в личный кабинет hoster.by.'],
                    ['text' => 'В меню «Домены» напротив нужного домена нажмите «Управление».'],
                    ['text' => 'Откройте вкладку «Расширенный редактор» или включите «Использовать расширенный DNS-редактор».'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Сохраните изменения.'],
                ],
                'notes' => [],
                'helpUrl' => 'https://hoster.by/',
                'helpLabel' => 'Сайт hoster.by',
                'requiresExternalDnsWarning' => true,
                'variants' => null,
            ],

            self::KEY_PS_KZ => [
                'label' => 'PS.kz / PS Cloud',
                'title' => 'Как это сделать в PS.kz',
                'intro' => 'Если домен и DNS управляются в PS Cloud Services, записи задаются в консоли.',
                'steps' => [
                    ['text' => 'Войдите в консоль PS Cloud Services.'],
                    ['text' => 'Откройте «Доменные имена» и выберите ваш домен.'],
                    ['text' => 'Откройте управление DNS-зоной / DNS-записями.'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Сохраните изменения.'],
                ],
                'notes' => [
                    'Если у вас хостинг в Plesk: «Сайты и домены» → нужный домен → «Настройки DNS» — те же записи.',
                    'Если DNS нужно сначала перевести на PS.kz, укажите NS ns1.ps.kz, ns2.ps.kz, ns3.ps.kz (по инструкции провайдера).',
                ],
                'helpUrl' => 'https://ps.kz/',
                'helpLabel' => 'PS.kz',
                'requiresExternalDnsWarning' => true,
                'variants' => null,
            ],

            self::KEY_NIC_UA => [
                'label' => 'NIC.UA',
                'title' => 'Как это сделать в NIC.UA',
                'intro' => 'В NIC.UA записи задаются в разделе серверов имён, если зона обслуживается у NIC.UA.',
                'steps' => [
                    ['text' => 'Зайдите в личный кабинет NIC.UA.'],
                    ['text' => 'Откройте «Домены» и нажмите на значок настроек у вашего домена.'],
                    ['text' => 'Убедитесь, что выбраны серверы имён NIC.UA. Если указаны другие NS, переключите на NIC.UA и сохраните (если это подходит вашей схеме).'],
                    ['text' => 'Откройте «Серверы имён (NS)», нажмите настройки у домена и выберите «Изменить» у блока «DNS-записи».'],
                    ['text' => 'Добавьте или обновите записи из блока «Что нужно добавить в DNS» на этой странице.'],
                    ['text' => 'Сохраните изменения.'],
                ],
                'notes' => [
                    'После регистрации домен может стоять на «парковых» NS — перед правками проверьте, какие NS сейчас используются.',
                ],
                'helpUrl' => 'https://nic.ua/',
                'helpLabel' => 'NIC.UA',
                'requiresExternalDnsWarning' => true,
                'variants' => null,
            ],
        ];
    }
}
