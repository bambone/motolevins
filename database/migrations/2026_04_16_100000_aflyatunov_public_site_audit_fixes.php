<?php

use Database\Seeders\Tenant\AflyatunovExpertBootstrap;
use Illuminate\Database\Migrations\Migration;

/**
 * Tenant aflyatunov: контакты (VK/Telegram + tel: через секцию), каналы формы, FAQ «где занятия»,
 * ориентиры цен, расширенный «О тренере», meta description с ценой.
 */
return new class extends Migration
{
    public function up(): void
    {
        AflyatunovExpertBootstrap::patchAflyatunovPublicSiteAudit2026();
    }

    public function down(): void
    {
        // Не откатываем контент демо-тенанта.
    }
};
