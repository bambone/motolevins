<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_units', function (Blueprint $table) {
            $table->string('unit_label')->nullable()->after('motorcycle_id');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('rental_units', function (Blueprint $table) {
            $table->dropColumn(['unit_label', 'notes']);
        });
    }
};
