<?php

use App\TenantSiteSetup\SetupSessionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant site setup framework (readiness + guided mode).
 *
 * Single active session per (tenant_id, user_id): enforced in {@see SetupSessionService}
 * when DB cannot use a partial unique index (MySQL / SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_setup_item_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('item_key', 120);
            $table->string('category_key', 64)->nullable();
            $table->string('current_status', 32)->default('pending');
            $table->string('applicability_status', 40)->default('applicable');
            $table->string('snooze_reason_code', 64)->nullable();
            $table->string('not_needed_reason_code', 64)->nullable();
            $table->text('reason_comment')->nullable();
            $table->timestamp('snooze_until')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('completed_value_json')->nullable();
            $table->string('completion_source', 32)->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamp('last_completion_check_at')->nullable();
            $table->json('last_completion_result_json')->nullable();
            $table->string('last_target_route_name', 191)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'item_key']);
            $table->index(['tenant_id', 'current_status']);
            $table->index(['tenant_id', 'applicability_status']);
            $table->index(['tenant_id', 'category_key']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('tenant_setup_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('session_status', 24)->default('active');
            $table->string('current_item_key', 120)->nullable();
            $table->string('current_route_name', 191)->nullable();
            $table->string('journey_version', 64)->nullable();
            $table->unsignedInteger('step_index')->default(0);
            $table->json('visible_step_keys_json')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'session_status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_setup_sessions');
        Schema::dropIfExists('tenant_setup_item_states');
    }
};
