<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->boolean('uses_fleet_units')->default(false)->after('tenant_id');
            $table->string('location_mode', 32)->default('everywhere')->after('uses_fleet_units');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn(['uses_fleet_units', 'location_mode']);
        });
    }
};
