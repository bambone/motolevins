<?php

declare(strict_types=1);

/**
 * Загружается до vendor/autoload и до Laravel.
 *
 * Если запустить `phpunit` с `--no-configuration` или без env из phpunit.xml,
 * подтянется .env с MySQL — трейт RefreshDatabase делает migrate:fresh и сотрёт
 * реальную базу (в т.ч. users). Здесь принудительно изолируем тесты на sqlite :memory:.
 *
 * Явный opt-out (отдельная тестовая MySQL и т.п.): RENTBASE_TEST_USE_ENV_DATABASE=1
 */
if (! filter_var(getenv('RENTBASE_TEST_USE_ENV_DATABASE') ?: '', FILTER_VALIDATE_BOOLEAN)) {
    $_ENV['APP_ENV'] = 'testing';
    $_SERVER['APP_ENV'] = 'testing';
    putenv('APP_ENV=testing');

    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_ENV['DB_URL'] = '';
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=:memory:');
    putenv('DB_URL=');
}

// If a developer (or a script) ran `config:cache`, `bootstrap/cache/config.php` bakes .env
// and overrides phpunit.xml / TestCase putenv. Tests must see testing env (e.g. TENANCY_CENTRAL_DOMAINS).
// Drop the cache file before the framework loads so every test run is consistent.
$configCache = dirname(__DIR__).'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    @unlink($configCache);
}

require dirname(__DIR__).'/vendor/autoload.php';
