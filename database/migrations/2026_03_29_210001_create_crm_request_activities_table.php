<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_request_id')->constrained('crm_requests')->cascadeOnDelete();
            $table->string('type', 64);
            $table->json('meta')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['crm_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_request_activities');
    }
};
