<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Статический seed без git/shell. is_published = 1 для прямого insert.
 *
 * @return list<array<string, mixed>>
 */
if (! function_exists('platform_product_changelog_seed_rows')) {
function platform_product_changelog_seed_rows(string $now): array
{
    return [
        [
            'entry_date' => '2026-03-17',
            'title' => 'Старт платформы RentBase для вашего бизнеса',
            'summary' => 'Публичный сайт на домене клиента: каталог, цены, отзывы, контакты — база для бронирований и кабинета.',
            'body' => "### Что появилось\n- Маршруты публичного сайта.\n- Единая структура под дальнейшее развитие.\n\n### Где смотреть\nСайт открывается на домене клиента (не `/admin`).\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-18',
            'title' => 'Удобный сайт на телефоне и PWA',
            'summary' => 'Адаптивная вёрстка, шапка с меню; на поддерживаемых браузерах можно добавить ярлык сайта на экран.',
            'body' => "### Что улучшили\n- Компоненты главной и навигация.\n- Манифест и service worker (PWA).\n\n### Что сделать\nПроверьте главную с телефона; логотип — в **Настройки** в левом меню.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-19',
            'title' => 'Главная: доверие и медиа',
            'summary' => 'Блоки доверия, аккуратная типографика, видео в hero с регулятором громкости (где включено в теме).',
            'body' => "### Где настраивать\n**Контент → Страницы** и секции страницы; бренд — **Настройки**.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-20',
            'title' => 'Кабинет клиента отдельно от консоли платформы',
            'summary' => 'Команда арендатора заходит в `/admin` на своём домене; консоль платформы — на отдельном хосте.',
            'body' => "### Зачем\nПроще права и меньше путаницы.\n\n### Проверьте\n**Настройки → Команда**: роли и статусы.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-27',
            'title' => 'Каталог изолирован по клиенту',
            'summary' => 'Техника и карточки каталога не пересекаются между тенантами.',
            'body' => "### Где работать\n**Каталог → Объекты каталога**, **Единицы парка**.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-28',
            'title' => 'Домены и тема публичного сайта',
            'summary' => 'Несколько доменов, статусы подключения, выбор темы оформления (`theme_key`).',
            'body' => "### В кабинете\n**Инфраструктура → Свой домен**.\nТема задаётся при запуске/в настройках по процессу вашей площадки.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-29',
            'title' => 'CRM: обращения и заявки',
            'summary' => 'Карточки CRM, статусы, ответственный, история; связь с лидами.',
            'body' => "### Где\n**Операции → Обращения** (или **Заявки** — по терминологии ниши).\n\n### Менеджеру\nВедите статус до закрытия, назначайте ответственного.\n",
            'sort_weight' => 20,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-29',
            'title' => 'Команда, роли и почта тенанта',
            'summary' => 'Состав кабинета и роли; лимиты и журнал исходящей почты по клиенту.',
            'body' => "### Где\n**Настройки → Команда**.\nПочтовые лимиты и логи помогают поддержке при сбоях доставки.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-03-30',
            'title' => 'Терминология под нишу и стол оператора CRM',
            'summary' => 'Подписи меню под ваш бизнес; удобнее обрабатывать входящие; выгрузки данных (exports).',
            'body' => "### Где\n**Настройки → Терминология**; CRM — **Операции**.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-01',
            'title' => 'Темы и формы настроек',
            'summary' => 'Пути ассетов тем и формы настроек/CRM в одном стиле.',
            'body' => "После смены темы проверьте публичный сайт и медиа в секциях страниц.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-02',
            'title' => 'Заявки, брони и календарь',
            'summary' => 'Списки лидов и бронирований; календарь; favicon панели для ориентации во вкладках.',
            'body' => "### Где\n**Операции → Заявки**, **Бронирования**, **Календарь бронирований**.\nРучное оформление брони — в соответствующих формах раздела бронирований (по вашей сборке).\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-03',
            'title' => 'Квоты хранилища и секции главной',
            'summary' => 'Учёт объёма файлов клиента; блок каталога на главной через конструктор страницы.',
            'body' => "### Где\n**Инфраструктура → Мониторинг и лимиты**; страницы — **Контент → Страницы**.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-04',
            'title' => 'Конструктор секций страниц',
            'summary' => 'Собирайте главную и посадочные из секций без правки кода.',
            'body' => "1. **Контент → Страницы**.\n2. Секции: тексты, медиа, контакты.\n3. Проверьте вид на мобильном.\n",
            'sort_weight' => 20,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-04',
            'title' => 'SEO-файлы: robots, sitemap, llms',
            'summary' => 'Генерация и управление файлами для поисковиков и llms.txt на домене клиента.',
            'body' => "### Где\n**Маркетинг → SEO-файлы**.\nПосле смены домена пересоберите файлы и откройте их по прямым URL.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-05',
            'title' => 'SEO по умолчанию при запуске клиента',
            'summary' => 'При онбординге подставляются базовые SEO-поля — меньше ручной рутины.',
            'body' => "Донастройте заголовки и описания в **Настройки** и **SEO-файлы** после запуска.\n",
            'sort_weight' => 10,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-05',
            'title' => 'Уведомления: правила, получатели, доставка',
            'summary' => 'Правила и каналы; планировщик и дедупликация; история доставок; связь с бронированиями.',
            'body' => "### Где\n**Настройки → Правила уведомлений**, **Получатели уведомлений**; журнал — **История доставок**.\n\nСделайте тест (например новое бронирование) и убедитесь, что сообщение пришло.\n",
            'sort_weight' => 20,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'entry_date' => '2026-04-05',
            'title' => 'Страница «Что нового» и ссылка в меню',
            'summary' => 'Список обновлений только в **кабинете клиента**; пункт **Профиль → Что нового** открывает `/admin/whats-new` на домене тенанта.',
            'body' => "Публичной страницы changelog на маркетинге нет. Редактирование записей — **консоль платформы → Чейнджлог продукта**.\n",
            'sort_weight' => 5,
            'is_published' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ];
}
}

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_product_changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->integer('sort_weight')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('entry_date');
            $table->index('is_published');
            $table->index(['entry_date', 'is_published']);
        });

        $now = now()->toDateTimeString();
        foreach (array_chunk(platform_product_changelog_seed_rows($now), 50) as $chunk) {
            DB::table('platform_product_changelog_entries')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_product_changelog_entries');
    }
};