<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('bikes')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending');
            $table->unsignedInteger('price_per_day_snapshot');
            $table->unsignedInteger('total_price');
            $table->string('customer_name');
            $table->string('phone');
            $table->string('phone_normalized')->nullable();
            $table->string('source')->nullable();
            $table->text('customer_comment')->nullable();
            $table->timestamps();

            $table->index(['bike_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
