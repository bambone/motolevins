<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_domains', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_domains', 'verification_status')) {
                $table->dropColumn('verification_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_domains', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_domains', 'verification_status')) {
                $table->string('verification_status')->nullable();
            }
        });
    }
};
