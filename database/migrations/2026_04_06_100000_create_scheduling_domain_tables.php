<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'scheduling_module_enabled')) {
                $table->boolean('scheduling_module_enabled')->default(true)->after('mail_rate_limit_per_minute');
            }
            if (! Schema::hasColumn('tenants', 'calendar_integrations_enabled')) {
                $table->boolean('calendar_integrations_enabled')->default(false)->after('scheduling_module_enabled');
            }
            if (! Schema::hasColumn('tenants', 'scheduling_promo_free_until')) {
                $table->date('scheduling_promo_free_until')->nullable()->after('calendar_integrations_enabled');
            }
            if (! Schema::hasColumn('tenants', 'scheduling_integration_error_policy')) {
                $table->string('scheduling_integration_error_policy', 32)->default('warn_only')->after('scheduling_promo_free_until');
            }
            if (! Schema::hasColumn('tenants', 'scheduling_stale_busy_policy')) {
                $table->string('scheduling_stale_busy_policy', 32)->nullable()->after('scheduling_integration_error_policy');
            }
            if (! Schema::hasColumn('tenants', 'scheduling_default_write_calendar_subscription_id')) {
                $table->unsignedBigInteger('scheduling_default_write_calendar_subscription_id')->nullable()->after('scheduling_stale_busy_policy');
            }
        });

        Schema::create('scheduling_targets', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('target_type', 64);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('label');
            $table->boolean('scheduling_enabled')->default(false);
            $table->boolean('external_busy_enabled')->default(false);
            $table->boolean('internal_busy_enabled')->default(true);
            $table->boolean('auto_write_to_calendar_enabled')->default(false);
            $table->string('occupancy_scope_mode', 32)->default('generic');
            $table->string('calendar_usage_mode', 32)->default('disabled');
            $table->string('external_busy_effect', 32)->default('informational_only');
            $table->string('stale_busy_policy', 32)->nullable();
            $table->unsignedBigInteger('default_write_calendar_subscription_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->index(['scheduling_scope', 'tenant_id']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('scheduling_resources', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('resource_type', 32);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('timezone', 64)->default('UTC');
            $table->string('tentative_events_policy', 32)->default('treat_as_busy');
            $table->string('unconfirmed_requests_policy', 40)->default('confirmed_only');
            $table->unsignedBigInteger('default_write_calendar_subscription_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->index(['scheduling_scope', 'tenant_id']);
        });

        Schema::create('bookable_services', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('slot_step_minutes')->default(15);
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0);
            $table->unsignedSmallInteger('buffer_after_minutes')->default(0);
            $table->unsignedInteger('min_booking_notice_minutes')->default(120);
            $table->unsignedSmallInteger('max_booking_horizon_days')->default(60);
            $table->boolean('requires_confirmation')->default(true);
            $table->unsignedBigInteger('default_write_calendar_subscription_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_weight')->default(0);
            $table->timestamps();

            $table->unique(['scheduling_scope', 'tenant_id', 'slug']);
        });

        Schema::create('scheduling_target_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_target_id')->constrained('scheduling_targets')->cascadeOnDelete();
            $table->foreignId('scheduling_resource_id')->constrained('scheduling_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('assignment_strategy', 32)->default('first_available');
            $table->timestamps();

            $table->unique(['scheduling_target_id', 'scheduling_resource_id'], 'sched_tgt_resource_unique');
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_resource_id')->constrained('scheduling_resources')->cascadeOnDelete();
            $table->foreignId('applies_to_scheduling_target_id')->nullable()->constrained('scheduling_targets')->nullOnDelete();
            $table->foreignId('applies_to_bookable_service_id')->nullable()->constrained('bookable_services')->nullOnDelete();
            $table->string('rule_type', 32);
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at_local');
            $table->time('ends_at_local');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_resource_id')->constrained('scheduling_resources')->cascadeOnDelete();
            $table->foreignId('scheduling_target_id')->nullable()->constrained('scheduling_targets')->nullOnDelete();
            $table->foreignId('bookable_service_id')->nullable()->constrained('bookable_services')->nullOnDelete();
            $table->string('exception_type', 16);
            $table->dateTimeTz('starts_at_utc');
            $table->dateTimeTz('ends_at_utc');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('manual_busy_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('scheduling_target_id')->nullable()->constrained('scheduling_targets')->nullOnDelete();
            $table->foreignId('scheduling_resource_id')->nullable()->constrained('scheduling_resources')->nullOnDelete();
            $table->dateTimeTz('starts_at_utc');
            $table->dateTimeTz('ends_at_utc');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity', 16)->default('hard');
            $table->string('source', 32)->default('operator');
            $table->timestamps();

            $table->index(['scheduling_scope', 'tenant_id']);
            $table->index(['starts_at_utc', 'ends_at_utc']);
        });

        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('scheduling_resource_id')->nullable()->constrained('scheduling_resources')->nullOnDelete();
            $table->string('provider', 32);
            $table->string('access_mode', 32);
            $table->string('account_email')->nullable();
            $table->string('display_name')->nullable();
            $table->text('credentials_encrypted')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestampTz('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampTz('last_successful_sync_at')->nullable();
            $table->unsignedInteger('stale_after_seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scheduling_scope', 'tenant_id']);
        });

        Schema::create('calendar_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_connection_id')->constrained('calendar_connections')->cascadeOnDelete();
            $table->string('external_calendar_id');
            $table->string('title')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->boolean('use_for_busy')->default(true);
            $table->boolean('use_for_write')->default(false);
            $table->text('sync_token')->nullable();
            $table->string('external_etag')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_successful_sync_at')->nullable();
            $table->unsignedInteger('stale_after_seconds')->nullable();
            $table->timestamps();
        });

        Schema::table('scheduling_targets', function (Blueprint $table) {
            $table->foreign('default_write_calendar_subscription_id', 'sched_tgt_wr_sub_fk')
                ->references('id')->on('calendar_subscriptions')->nullOnDelete();
        });

        Schema::table('scheduling_resources', function (Blueprint $table) {
            $table->foreign('default_write_calendar_subscription_id', 'sched_res_wr_sub_fk')
                ->references('id')->on('calendar_subscriptions')->nullOnDelete();
        });

        Schema::table('bookable_services', function (Blueprint $table) {
            $table->foreign('default_write_calendar_subscription_id', 'book_svc_wr_sub_fk')
                ->references('id')->on('calendar_subscriptions')->nullOnDelete();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('scheduling_default_write_calendar_subscription_id', 'ten_sched_wr_sub_fk')
                ->references('id')->on('calendar_subscriptions')->nullOnDelete();
        });

        Schema::create('calendar_occupancy_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_subscription_id')->constrained('calendar_subscriptions')->cascadeOnDelete();
            $table->string('mapping_type', 32);
            $table->foreignId('scheduling_target_id')->nullable()->constrained('scheduling_targets')->nullOnDelete();
            $table->foreignId('scheduling_resource_id')->nullable()->constrained('scheduling_resources')->nullOnDelete();
            $table->string('match_mode', 32)->default('entire_calendar');
            $table->string('match_confidence', 32)->default('high');
            $table->json('rules_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('external_busy_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_resource_id')->nullable()->constrained('scheduling_resources')->cascadeOnDelete();
            $table->foreignId('scheduling_target_id')->nullable()->constrained('scheduling_targets')->nullOnDelete();
            $table->foreignId('calendar_subscription_id')->nullable()->constrained('calendar_subscriptions')->nullOnDelete();
            $table->dateTimeTz('starts_at_utc');
            $table->dateTimeTz('ends_at_utc');
            $table->string('source_event_id')->nullable();
            $table->boolean('is_tentative')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['scheduling_resource_id', 'starts_at_utc', 'ends_at_utc'], 'ext_busy_res_time_idx');
        });

        Schema::create('calendar_event_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_subscription_id')->constrained('calendar_subscriptions')->cascadeOnDelete();
            $table->foreignId('scheduling_resource_id')->nullable()->constrained('scheduling_resources')->nullOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('external_calendar_id')->nullable();
            $table->string('external_event_id')->nullable();
            $table->string('external_event_uid')->nullable();
            $table->string('provider_etag')->nullable();
            $table->string('sync_direction', 24)->default('write_only');
            $table->string('link_status', 24)->default('active');
            $table->timestampTz('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
        });

        Schema::create('appointment_holds', function (Blueprint $table) {
            $table->id();
            $table->string('scheduling_scope', 16);
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('bookable_service_id')->constrained('bookable_services')->cascadeOnDelete();
            $table->foreignId('scheduling_resource_id')->constrained('scheduling_resources')->cascadeOnDelete();
            $table->foreignId('crm_request_id')->nullable()->constrained('crm_requests')->nullOnDelete();
            $table->dateTimeTz('starts_at_utc');
            $table->dateTimeTz('ends_at_utc');
            $table->string('status', 24)->default('hold');
            $table->string('source', 32)->default('public_form');
            $table->timestampTz('expires_at');
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone', 64)->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['bookable_service_id', 'scheduling_resource_id', 'starts_at_utc'], 'appt_hold_svc_res_time_idx');
            $table->index(['status', 'expires_at'], 'appt_hold_status_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_holds');
        Schema::dropIfExists('calendar_event_links');
        Schema::dropIfExists('external_busy_blocks');
        Schema::dropIfExists('calendar_occupancy_mappings');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign('ten_sched_wr_sub_fk');
        });
        Schema::table('bookable_services', function (Blueprint $table) {
            $table->dropForeign('book_svc_wr_sub_fk');
        });
        Schema::table('scheduling_resources', function (Blueprint $table) {
            $table->dropForeign('sched_res_wr_sub_fk');
        });
        Schema::table('scheduling_targets', function (Blueprint $table) {
            $table->dropForeign('sched_tgt_wr_sub_fk');
        });

        Schema::dropIfExists('calendar_subscriptions');
        Schema::dropIfExists('calendar_connections');
        Schema::dropIfExists('manual_busy_blocks');
        Schema::dropIfExists('availability_exceptions');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('scheduling_target_resource');
        Schema::dropIfExists('bookable_services');
        Schema::dropIfExists('scheduling_resources');
        Schema::dropIfExists('scheduling_targets');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'scheduling_module_enabled',
                'calendar_integrations_enabled',
                'scheduling_promo_free_until',
                'scheduling_integration_error_policy',
                'scheduling_stale_busy_policy',
                'scheduling_default_write_calendar_subscription_id',
            ]);
        });
    }
};
