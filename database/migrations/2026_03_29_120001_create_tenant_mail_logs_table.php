<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('correlation_id')->unique();
            $table->string('queue_job_id')->nullable();
            $table->string('mailable_class');
            $table->string('mail_type', 100);
            $table->string('mail_group', 64)->default('transactional');
            $table->string('to_email');
            $table->string('subject')->nullable();
            $table->string('status', 32);
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('throttled_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['mail_type', 'created_at']);
            $table->index('mailable_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_mail_logs');
    }
};
