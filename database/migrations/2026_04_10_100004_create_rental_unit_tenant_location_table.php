<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_unit_tenant_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_unit_id')->constrained('rental_units')->cascadeOnDelete();
            $table->foreignId('tenant_location_id')->constrained('tenant_locations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rental_unit_id', 'tenant_location_id'], 'rental_unit_tenant_loc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_unit_tenant_location');
    }
};
