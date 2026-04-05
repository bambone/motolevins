<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Schema::hasTable guards keep deploys idempotent when tables already exist.
         * Trade-off: this migration does not repair partially created or drifted schemas;
         * handle that with a dedicated repair migration or manual ops if needed.
         */
        if (! Schema::hasTable('notification_destinations')) {
            Schema::create('notification_destinations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('type', 32);
                $table->string('status', 32)->default('draft');
                $table->boolean('is_shared')->default(false);
                $table->json('config_json')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('last_error_at')->nullable();
                $table->text('last_error_message')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('notification_subscriptions')) {
            Schema::create('notification_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('event_key', 128);
                $table->boolean('enabled')->default(true);
                $table->json('conditions_json')->nullable();
                $table->json('schedule_json')->nullable();
                $table->string('severity_min', 32)->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'event_key', 'enabled']);
            });
        }

        if (! Schema::hasTable('notification_subscription_destinations')) {
            Schema::create('notification_subscription_destinations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_id')->constrained('notification_subscriptions')->cascadeOnDelete();
                $table->foreignId('destination_id')->constrained('notification_destinations')->cascadeOnDelete();
                $table->string('delivery_mode', 32)->default('immediate');
                $table->unsignedInteger('delay_seconds')->nullable();
                $table->unsignedSmallInteger('order_index')->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->unique(['subscription_id', 'destination_id'], 'notif_sub_dest_unique');
            });
        }

        if (! Schema::hasTable('notification_events')) {
            Schema::create('notification_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('event_key', 128);
                $table->string('subject_type', 128);
                $table->unsignedBigInteger('subject_id');
                $table->string('severity', 32);
                $table->string('dedupe_key', 191)->nullable();
                $table->json('payload_json');
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('occurred_at');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['tenant_id', 'event_key']);
                $table->index(['tenant_id', 'subject_type', 'subject_id']);
                $table->unique(['tenant_id', 'event_key', 'dedupe_key']);
            });
        }

        if (! Schema::hasTable('notification_deliveries')) {
            Schema::create('notification_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('event_id')->constrained('notification_events')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('notification_subscriptions')->nullOnDelete();
                $table->foreignId('destination_id')->constrained('notification_destinations')->cascadeOnDelete();
                $table->string('channel_type', 32);
                $table->string('status', 32)->default('queued');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->string('provider_message_id', 255)->nullable();
                $table->json('response_json')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['event_id', 'status']);
            });
        }

        if (! Schema::hasTable('notification_delivery_attempts')) {
            Schema::create('notification_delivery_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_id')->constrained('notification_deliveries')->cascadeOnDelete();
                $table->unsignedSmallInteger('attempt_no');
                $table->string('status', 32);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('response_json')->nullable();
                $table->timestamps();

                $table->unique(['delivery_id', 'attempt_no']);
            });
        }

        if (! Schema::hasTable('notification_push_subscriptions')) {
            Schema::create('notification_push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('destination_id')->nullable()->constrained('notification_destinations')->nullOnDelete();
                $table->text('endpoint');
                $table->string('public_key', 255);
                $table->string('auth_token', 255);
                $table->text('user_agent')->nullable();
                $table->string('device_label', 255)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_push_subscriptions');
        Schema::dropIfExists('notification_delivery_attempts');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('notification_subscription_destinations');
        Schema::dropIfExists('notification_subscriptions');
        Schema::dropIfExists('notification_destinations');
    }
};
