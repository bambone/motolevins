<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['leads', 'crm_requests', 'bookings'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'preferred_contact_channel')) {
                    $blueprint->string('preferred_contact_channel', 32)->nullable()->after('phone');
                }
                if (! Schema::hasColumn($table, 'preferred_contact_value')) {
                    $blueprint->text('preferred_contact_value')->nullable()->after('preferred_contact_channel');
                }
                if (! Schema::hasColumn($table, 'visitor_contact_channels_json')) {
                    $blueprint->json('visitor_contact_channels_json')->nullable()->after('preferred_contact_value');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'crm_requests', 'bookings'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (Schema::hasColumn($table, 'visitor_contact_channels_json')) {
                    $blueprint->dropColumn('visitor_contact_channels_json');
                }
                if (Schema::hasColumn($table, 'preferred_contact_value')) {
                    $blueprint->dropColumn('preferred_contact_value');
                }
                if (Schema::hasColumn($table, 'preferred_contact_channel')) {
                    $blueprint->dropColumn('preferred_contact_channel');
                }
            });
        }
    }
};
