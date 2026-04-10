<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

use App\Filament\Tenant\Resources\CalendarConnectionResource;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use Illuminate\Support\HtmlString;

/**
 * Пошаговые подсказки для формы {@see CalendarConnectionResource}.
 */
final class CalendarConnectionFormGuide
{
    public static function panel(?string $provider, ?string $accessMode): HtmlString
    {
        $p = is_string($provider) ? CalendarProviderType::tryFrom($provider) : null;
        $m = is_string($accessMode) ? CalendarAccessMode::tryFrom($accessMode) : null;

        $parts = ['<div class="space-y-4 text-sm leading-relaxed">'];

        if ($p === null) {
            $parts[] = self::card(
                'С чего начать',
                '<p class="text-gray-600 dark:text-gray-300">Сначала выберите <strong>провайдера календаря</strong>. После этого выберите <strong>режим доступа</strong> — список режимов и поля формы подстроятся под ваш выбор.</p>'
            );
            $parts[] = '</div>';

            return new HtmlString(implode('', $parts));
        }

        $parts[] = self::providerCard($p);

        if ($m === null) {
            $parts[] = self::card(
                'Следующий шаг',
                '<p class="text-gray-600 dark:text-gray-300">Выберите <strong>режим доступа</strong> — ниже появятся инструкции, что именно копировать и куда заходить.</p>'
            );
        } else {
            $parts[] = self::combinationCard($p, $m);
        }

        $parts[] = '</div>';

        return new HtmlString(implode('', $parts));
    }

    public static function credentialsFieldHelper(?string $provider, ?string $accessMode): string
    {
        $p = is_string($provider) ? CalendarProviderType::tryFrom($provider) : null;
        $m = is_string($accessMode) ? CalendarAccessMode::tryFrom($accessMode) : null;
        if ($p === null || $m === null) {
            return 'После выбора провайдера и режима здесь будет точное описание формата.';
        }

        return match ([$p, $m]) {
            [CalendarProviderType::Google, CalendarAccessMode::Oauth] => 'Вставьте JSON с токенами после OAuth (например refresh_token, access_token, expiry) или оставьте пустым до завершения кнопки «Подключить Google» в будущей версии. Не публикуйте это поле в скриншотах.',
            [CalendarProviderType::Google, CalendarAccessMode::AppPassword] => 'Вставьте 16-символьный пароль приложения Google (без пробелов). Логин — в поле «Аккаунт (email)» выше. Для CalDAV пароль передаётся вместе с URL из инструкции слева.',
            [CalendarProviderType::Google, CalendarAccessMode::ServiceToken] => 'Вставьте полный JSON ключ сервисного аккаунта (файл из Google Cloud → IAM → ключи). Один объект JSON, без комментариев. Для Workspace может понадобиться делегирование домена — уточните у администратора.',
            [CalendarProviderType::Yandex, CalendarAccessMode::Oauth] => 'Вставьте JSON с OAuth-токенами Яндекса (access_token, refresh_token и т.д.) после прохождения авторизации, либо следуйте регламенту вашей интеграции.',
            [CalendarProviderType::Yandex, CalendarAccessMode::AppPassword] => 'Вставьте пароль приложения из Яндекс ID (раздел «Пароли приложений») или основной пароль, если разрешён вход в CalDAV (менее безопасно).',
            [CalendarProviderType::Yandex, CalendarAccessMode::ServiceToken] => 'Обычно сюда кладут ответ OAuth или долгоживущий токен в JSON. Если не используете — выберите другой режим.',
            [CalendarProviderType::Mailru, CalendarAccessMode::AppPassword] => 'Вставьте пароль от почты Mail.ru, к которой привязан календарь, или отдельный пароль для внешних приложений, если включали.',
            [CalendarProviderType::Mailru, CalendarAccessMode::Oauth] => 'Если доступен OAuth Mail.ru — вставьте JSON с токенами. Иначе чаще используют режим «Пароль приложения».',
            [CalendarProviderType::Mailru, CalendarAccessMode::ServiceToken] => 'Сервисный токен или JSON по документации Mail.ru / Cloud API, если применимо к вашему сценарию.',
            default => 'Заполните согласно инструкции в блоке выше.',
        };
    }

    /**
     * OAuth-клиент Google на сервере: все три значения заданы в окружении.
     */
    private static function googlePlatformOAuthConfigured(): bool
    {
        $g = config('scheduling.google', []);

        foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
            $v = $g[$key] ?? null;
            if (! is_string($v) || trim($v) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * OAuth-клиент Яндекса на сервере (для сценариев с серверным OAuth).
     */
    private static function yandexPlatformOAuthConfigured(): bool
    {
        $y = config('scheduling.yandex', []);

        foreach (['client_id', 'client_secret'] as $key) {
            $v = $y[$key] ?? null;
            if (! is_string($v) || trim($v) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Сообщение для пользователя кабинета клиента: ключи на стороне платформы не готовы.
     */
    private static function tenantContactPlatformAdminBanner(string $leadHtml, bool $suggestOtherAccessMode = false): string
    {
        $alt = $suggestOtherAccessMode
            ? '<p class="mt-2">Пока администратор не включит интеграцию на сервере, выберите <strong>другой режим доступа</strong> к этому провайдеру (например CalDAV с паролем приложения), если он подходит под вашу политику безопасности.</p>'
            : '';

        return '<div class="rounded-lg border border-red-200/90 bg-red-50/95 p-4 text-sm leading-relaxed text-red-950 dark:border-red-500/35 dark:bg-red-500/15 dark:text-red-100">'
            .'<p class="font-semibold">Нужна настройка платформы</p>'
            .'<div class="mt-2">'.$leadHtml.'</div>'
            .$alt
            .'<p class="mt-3 text-xs opacity-95">Обратитесь к <strong>администратору платформы RentBase</strong> — подключение ключей и доступность этого сценария настраиваются на сервере, не в вашем кабинете.</p>'
            .'</div>';
    }

    private static function card(string $title, string $bodyHtml): string
    {
        $t = e($title);

        return '<div class="rounded-xl border border-gray-200 bg-gray-50/90 p-4 dark:border-white/10 dark:bg-white/5">'
            ."<h3 class=\"mb-2 text-sm font-semibold text-gray-950 dark:text-white\">{$t}</h3>"
            .'<div class="text-gray-600 dark:text-gray-300">'.$bodyHtml.'</div></div>';
    }

    private static function providerCard(CalendarProviderType $p): string
    {
        $body = match ($p) {
            CalendarProviderType::Google => self::googleProviderHtml(),
            CalendarProviderType::Yandex => self::yandexProviderHtml(),
            CalendarProviderType::Mailru => self::mailruProviderHtml(),
        };

        return self::card('Провайдер: '.$p->label(), $body);
    }

    private static function googleProviderHtml(): string
    {
        if (! self::googlePlatformOAuthConfigured()) {
            return self::tenantContactPlatformAdminBanner(
                '<p>Для входа через <strong>Google OAuth</strong> на сервере платформы не заданы все обязательные параметры (клиент OAuth и адрес возврата после авторизации).</p>'
                .'<p class="mt-2 text-xs opacity-90">Режимы без серверного OAuth (например <strong>пароль приложения + CalDAV</strong>) могут работать без этих ключей.</p>',
                true,
            )
                .'<details class="mt-4 rounded-lg border border-gray-200 bg-white/60 p-3 text-xs dark:border-white/10 dark:bg-white/5">'
                .'<summary class="cursor-pointer font-medium text-gray-800 dark:text-gray-200">Техническая справка для администратора платформы</summary>'
                .'<p class="mt-2 text-gray-600 dark:text-gray-400">Нужны непустые переменные окружения: <code>SCHEDULING_GOOGLE_CLIENT_ID</code>, <code>SCHEDULING_GOOGLE_CLIENT_SECRET</code>, <code>SCHEDULING_GOOGLE_REDIRECT_URI</code>. После изменений — перезапуск PHP / очистка кэша конфигурации.</p>'
                .'</details>';
        }

        $redirectConfigured = trim((string) (config('scheduling.google.redirect_uri') ?? ''));
        $callbackRouteUrl = route('scheduling.oauth.google.callback', [], true);
        $urlsMatch = $redirectConfigured !== '' && self::urlsSameForOAuthRedirect($redirectConfigured, $callbackRouteUrl);

        $audienceNote = '<div class="rounded-lg border border-amber-200/80 bg-amber-50/90 p-3 text-xs leading-relaxed text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">'
            .'<p class="font-semibold">Если вы в кабинете клиента (арендатор)</p>'
            .'<p class="mt-1">Подключение календаря делается здесь, в форме. Править <code>.env</code>, исходники или Google Cloud <strong>вам не нужно</strong> — это на стороне команды, которая обслуживает платформу RentBase.</p>'
            .'<p class="mt-2 font-semibold">Один OAuth-клиент на всё развёртывание</p>'
            .'<p class="mt-1">У каждого тенанта <strong>не</strong> свой отдельный Client ID в Google: один проект OAuth в Google Cloud обслуживает всех клиентов этого сервера; у разных компаний различаются только их личные токены после входа в Google.</p>'
            .'</div>';

        $redirectLine = '<p class="mt-2"><strong>Authorized redirect URIs</strong> в Google Cloud должны включать <strong>точно</strong> тот же URL, что в <code>SCHEDULING_GOOGLE_REDIRECT_URI</code> на сервере:</p>'
            .'<p class="mt-1"><code class="rounded bg-gray-200 px-1 py-0.5 text-xs dark:bg-white/10">'.e($redirectConfigured).'</code></p>'
            .'<p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Ожидаемый URL маршрута приложения (при текущем <code>APP_URL</code>): <code class="rounded bg-gray-200 px-1 py-0.5 text-xs dark:bg-white/10">'.e($callbackRouteUrl).'</code>'
            .' — обычно переменная окружения и этот адрес <strong>совпадают</strong>; иначе Google вернёт ошибку <code>redirect_uri_mismatch</code>.</p>';

        if (! $urlsMatch && $redirectConfigured !== '') {
            $redirectLine .= '<p class="mt-2 rounded border border-amber-400/50 bg-amber-100/80 p-2 text-xs text-amber-950 dark:border-amber-500/40 dark:bg-amber-500/20 dark:text-amber-50">'
                .'Сейчас <code>SCHEDULING_GOOGLE_REDIRECT_URI</code> и URL маршрута различаются. Проверьте <code>APP_URL</code> и путь: в коде callback — <code>/scheduling/oauth/google/callback</code>, а не <code>/google-calendar/callback</code>.</p>';
        }

        return $audienceNote
            .'<p class="mt-3 text-xs font-medium text-gray-600 dark:text-gray-400">Дальше — чеклист для администратора платформы / DevOps, который один раз настраивает Google Cloud и секреты сервера.</p>'
            .'<ol class="mt-2 list-decimal space-y-2 pl-5">'
            .'<li>Откройте <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Google Cloud Console</a> → проект (или создайте).</li>'
            .'<li><strong>APIs &amp; Services → Library</strong> → подключите <strong>Google Calendar API</strong>.</li>'
            .'<li><strong>OAuth consent screen</strong> — тип (Internal/External), контакты, домены при необходимости.</li>'
            .'<li><strong>Credentials → Create credentials → OAuth client ID</strong> → тип <strong>Web application</strong>.'
            .$redirectLine
            .'</li>'
            .'<li><strong>Client ID</strong> и <strong>Client secret</strong> сохраните в конфигурации сервера платформы (переменные <code>SCHEDULING_GOOGLE_CLIENT_ID</code>, <code>SCHEDULING_GOOGLE_CLIENT_SECRET</code>) — не в поля этой формы.</li>'
            .'</ol>'
            .'<p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Поле «Учётные данные» ниже — для токенов <em>вашего</em> подключения (например JSON с refresh token после входа в Google), а не для client id платформы.</p>';
    }

    private static function urlsSameForOAuthRedirect(string $a, string $b): bool
    {
        return rtrim($a, '/') === rtrim($b, '/');
    }

    private static function yandexProviderHtml(): string
    {
        $oauthNotice = '';
        if (! self::yandexPlatformOAuthConfigured()) {
            $oauthNotice = self::tenantContactPlatformAdminBanner(
                '<p>Если вы планируете подключение через <strong>OAuth Яндекса</strong> с участием сервера платформы, сейчас на сервере не заданы <code>SCHEDULING_YANDEX_CLIENT_ID</code> / <code>SCHEDULING_YANDEX_CLIENT_SECRET</code>.</p>',
                true,
            );
        }

        return $oauthNotice
            .'<ol class="list-decimal space-y-2 pl-5">'
            .'<li>Календарь: <a href="https://calendar.yandex.ru/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">calendar.yandex.ru</a> — войдите под нужным аккаунтом.</li>'
            .'<li>Для <strong>CalDAV</strong> сервер обычно: <code class="rounded bg-gray-200 px-1 text-xs dark:bg-white/10">https://caldav.yandex.ru</code>, логин — полный email, пароль — из Яндекс ID (см. режим «Пароль приложения»).</li>'
            .'<li>Для <strong>OAuth</strong> зарегистрируйте приложение в <a href="https://oauth.yandex.ru/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Яндекс OAuth</a>, получите ID и секрет для сервера (хранятся в настройках платформы, не в этой форме).</li>'
            .'<li>Пароли приложений: <a href="https://id.yandex.ru/security" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Яндекс ID → Безопасность</a>.</li>'
            .'</ol>';
    }

    private static function mailruProviderHtml(): string
    {
        return '<ol class="list-decimal space-y-2 pl-5">'
            .'<li>Войдите в <a href="https://calendar.mail.ru/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Календарь Mail.ru</a> под тем же аккаунтом, что и почта.</li>'
            .'<li>Для <strong>CalDAV</strong> используйте адрес из справки Mail.ru (часто вида <code class="rounded bg-gray-200 px-1 text-xs dark:bg-white/10">https://caldav.mail.ru/</code> или персональный URL в настройках) и пароль почты / пароль для внешних приложений.</li>'
            .'<li>Если включена двухфакторная защита — создайте пароль для внешнего приложения в настройках почты.</li>'
            .'</ol>'
            .'<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Чаще всего выбирают режим «Пароль приложения» и указывают email в поле «Аккаунт».</p>';
    }

    private static function combinationCard(CalendarProviderType $p, CalendarAccessMode $m): string
    {
        $inner = match ([$p, $m]) {
            [CalendarProviderType::Google, CalendarAccessMode::Oauth] => self::googleOauthSteps(),
            [CalendarProviderType::Google, CalendarAccessMode::AppPassword] => self::googleAppPasswordSteps(),
            [CalendarProviderType::Google, CalendarAccessMode::ServiceToken] => self::googleServiceTokenSteps(),
            [CalendarProviderType::Yandex, CalendarAccessMode::Oauth] => self::yandexOauthSteps(),
            [CalendarProviderType::Yandex, CalendarAccessMode::AppPassword] => self::yandexAppPasswordSteps(),
            [CalendarProviderType::Yandex, CalendarAccessMode::ServiceToken] => self::yandexServiceTokenSteps(),
            [CalendarProviderType::Mailru, CalendarAccessMode::Oauth] => self::mailruOauthSteps(),
            [CalendarProviderType::Mailru, CalendarAccessMode::AppPassword] => self::mailruAppPasswordSteps(),
            [CalendarProviderType::Mailru, CalendarAccessMode::ServiceToken] => self::mailruServiceTokenSteps(),
        };

        return self::card('Режим: '.$m->label(), $inner);
    }

    private static function googleOauthSteps(): string
    {
        if (! self::googlePlatformOAuthConfigured()) {
            return self::tenantContactPlatformAdminBanner(
                '<p>Режим <strong>OAuth 2.0</strong> для Google сейчас недоступен: на сервере платформы не заданы все параметры Google OAuth.</p>',
                true,
            );
        }

        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Пользователь должен пройти экран входа Google и выдать доступ к календарю (scopes с Calendar).</li>'
            .'<li>После обмена <code>code</code> на токены сохраните <strong>refresh_token</strong> (и при необходимости access_token) в поле «Учётные данные» в формате JSON.</li>'
            .'<li>Кнопка входа через Google в интерфейсе и полный обмен кода на токены на сервере <strong>ещё в разработке</strong> — пока чаще используют ручной ввод JSON с токенами после OAuth вне приложения (скрипт, Postman и т.п.), при совпадении redirect URI в Google Cloud и на сервере.</li>'
            .'</ul>';
    }

    private static function googleAppPasswordSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Включите <strong>двухэтапную аутентификацию</strong> в аккаунте Google.</li>'
            .'<li>Создайте пароль приложения: <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Пароли приложений</a> (раздел может называться «App passwords»).</li>'
            .'<li>В поле «Аккаунт» укажите <strong>полный Gmail / Google Workspace email</strong>.</li>'
            .'<li>В «Учётные данные» вставьте только пароль приложения. CalDAV URL для Google: <code class="rounded bg-gray-200 px-1 text-xs dark:bg-white/10">https://apidata.googleusercontent.com/caldav/v2/<em>ваш_email</em>/events/</code> — подставьте email в путь (кодируйте <code>@</code> как <code>%40</code> при необходимости).</li>'
            .'</ul>';
    }

    private static function googleServiceTokenSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>В Google Cloud → <strong>IAM &amp; Admin → Service Accounts</strong> создайте сервисный аккаунт.</li>'
            .'<li>Создайте ключ типа <strong>JSON</strong> и скачайте файл.</li>'
            .'<li>Вставьте <strong>весь JSON</strong> в поле «Учётные данные». Поле «Аккаунт» можно оставить пустым или указать email сервисного аккаунта для заметки.</li>'
            .'<li>Для доступа к календарям пользователей домена (Workspace) часто нужно <strong>делегирование на уровне домена</strong> и client ID в админке Google Workspace.</li>'
            .'</ul>';
    }

    private static function yandexOauthSteps(): string
    {
        if (! self::yandexPlatformOAuthConfigured()) {
            return self::tenantContactPlatformAdminBanner(
                '<p>Режим <strong>OAuth</strong> для Яндекса сейчас недоступен: на сервере не заданы <code>SCHEDULING_YANDEX_CLIENT_ID</code> и <code>SCHEDULING_YANDEX_CLIENT_SECRET</code>.</p>',
                true,
            );
        }

        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Зарегистрируйте приложение на <a href="https://oauth.yandex.ru/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">oauth.yandex.ru</a>: Redirect URI, права на Календарь.</li>'
            .'<li>После авторизации пользователя сохраните выданные токены в JSON в поле «Учётные данные».</li>'
            .'<li>Client ID и secret приложения хранятся на стороне сервера платформы (<code>SCHEDULING_YANDEX_*</code>), не дублируйте их сюда без необходимости.</li>'
            .'</ul>';
    }

    private static function yandexAppPasswordSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li><a href="https://id.yandex.ru/security" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">Яндекс ID → Безопасность</a> → пароли приложений (если доступно) или используйте основной пароль там, где это разрешено политикой.</li>'
            .'<li>Адрес CalDAV: <code class="rounded bg-gray-200 px-1 text-xs dark:bg-white/10">https://caldav.yandex.ru</code>.</li>'
            .'<li>Логин — полный email Яндекса; пароль — в «Учётные данные».</li>'
            .'</ul>';
    }

    private static function yandexServiceTokenSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Используйте, если у вас уже есть долгоживущий токен или ответ API в JSON — вставьте в «Учётные данные».</li>'
            .'<li>Для большинства сценариев удобнее <strong>OAuth</strong> или <strong>пароль приложения + CalDAV</strong>.</li>'
            .'</ul>';
    }

    private static function mailruOauthSteps(): string
    {
        return '<div class="rounded-lg border border-amber-200/80 bg-amber-50/90 p-3 text-sm leading-relaxed text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">'
            .'<p class="font-semibold">OAuth Mail.ru</p>'
            .'<p class="mt-1">Сценарий зависит от настроек вашего развёртывания RentBase. Если вход через OAuth для Mail.ru не согласован — обратитесь к <strong>администратору платформы</strong> или выберите режим <strong>«Пароль приложения»</strong>.</p>'
            .'</div>'
            .'<ul class="mt-3 list-disc space-y-2 pl-5">'
            .'<li>Если OAuth доступен: после получения токенов сохраните их в JSON в поле «Учётные данные».</li>'
            .'</ul>';
    }

    private static function mailruAppPasswordSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Укажите <strong>email Mail.ru</strong> в поле «Аккаунт».</li>'
            .'<li>В «Учётные данные» вставьте пароль (или специальный пароль внешнего приложения из настроек почты).</li>'
            .'<li>Точный CalDAV URL возьмите из <a href="https://help.mail.ru/calendar/" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 underline dark:text-amber-400">справки Mail.ru по календарю</a> — он может отличаться для @mail.ru и корпоративных доменов.</li>'
            .'</ul>';
    }

    private static function mailruServiceTokenSteps(): string
    {
        return '<ul class="list-disc space-y-2 pl-5">'
            .'<li>Для редких интеграций через API Cloud — вставьте токен/JSON согласно документации Mail.ru.</li>'
            .'<li>Для обычного личного календаря чаще подходит режим с паролем.</li>'
            .'</ul>';
    }
}
