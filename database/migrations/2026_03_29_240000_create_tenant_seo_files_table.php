<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_seo_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('storage_disk', 64);
            $table->string('storage_path', 512);
            $table->string('public_url', 2048)->nullable();
            $table->boolean('exists')->default(false);
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_public_content_change_at')->nullable();
            $table->string('freshness_status', 48)->nullable();
            $table->foreignId('last_generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_generation_source', 32)->nullable();
            $table->string('backup_storage_path', 512)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_seo_files');
    }
};
