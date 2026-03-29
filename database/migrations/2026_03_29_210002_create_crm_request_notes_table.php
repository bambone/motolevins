<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_request_id')->constrained('crm_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('crm_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_request_notes');
    }
};
