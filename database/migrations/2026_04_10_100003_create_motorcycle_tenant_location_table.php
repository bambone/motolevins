<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motorcycle_tenant_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_location_id')->constrained('tenant_locations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['motorcycle_id', 'tenant_location_id'], 'moto_tenant_location_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motorcycle_tenant_location');
    }
};
