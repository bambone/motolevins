<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        if (! Schema::hasTable('review_import_sources')) {
            Schema::create('review_import_sources', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('provider', 40);
                $table->string('title')->nullable();
                $table->text('source_url');
                $table->string('external_owner_id')->nullable();
                $table->string('external_topic_id')->nullable();
                $table->string('external_place_id')->nullable();
                $table->string('status', 40)->default('draft');
                $table->json('settings_json')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->string('last_error_code')->nullable();
                $table->text('last_error_message')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'provider']);
            });
        }

        if (! Schema::hasTable('review_import_runs')) {
            Schema::create('review_import_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('review_import_source_id')->constrained('review_import_sources')->cascadeOnDelete();
                $table->string('status', 40);
                $table->unsignedInteger('fetched_count')->default(0);
                $table->unsignedInteger('candidate_count')->default(0);
                $table->unsignedInteger('duplicate_count')->default(0);
                $table->unsignedInteger('error_count')->default(0);
                $table->string('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('review_import_candidates')) {
            Schema::create('review_import_candidates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('review_import_source_id')->constrained('review_import_sources')->cascadeOnDelete();
                $table->foreignId('review_import_run_id')->nullable()->constrained('review_import_runs')->nullOnDelete();
                $table->string('provider', 40);
                $table->string('external_review_id')->nullable();
                $table->string('dedupe_hash', 64);
                $table->string('author_name')->nullable();
                $table->text('author_avatar_url')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->longText('body');
                $table->text('source_url')->nullable();
                $table->json('media_json')->nullable();
                $table->json('raw_payload_json')->nullable();
                $table->string('status', 40)->default('new');
                $table->foreignId('imported_review_id')->nullable()->constrained('reviews')->nullOnDelete();
                $table->timestamp('selected_at')->nullable();
                $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['tenant_id', 'provider', 'dedupe_hash']);
                $table->index(['tenant_id', 'status']);
            });
        }

        Schema::table('reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('reviews', 'source_provider')) {
                $table->string('source_provider', 40)->nullable()->after('source');
            }
            if (! Schema::hasColumn('reviews', 'source_external_id')) {
                $table->string('source_external_id')->nullable()->after('source_provider');
            }
            if (! Schema::hasColumn('reviews', 'source_url')) {
                $table->text('source_url')->nullable()->after('source_external_id');
            }
            if (! Schema::hasColumn('reviews', 'review_import_source_id')) {
                $table->foreignId('review_import_source_id')->nullable()->after('source_url')->constrained('review_import_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('reviews', 'review_import_candidate_id')) {
                $table->foreignId('review_import_candidate_id')->nullable()->after('review_import_source_id')->constrained('review_import_candidates')->nullOnDelete();
            }
            if (! Schema::hasColumn('reviews', 'source_original_body')) {
                $table->longText('source_original_body')->nullable()->after('review_import_candidate_id');
            }
            if (! Schema::hasColumn('reviews', 'source_payload_json')) {
                $table->json('source_payload_json')->nullable()->after('source_original_body');
            }
            if (! Schema::hasColumn('reviews', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('source_payload_json');
            }
        });

        Schema::table('reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('reviews', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->default(null)->change();
            }
        });

        Schema::table('reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('reviews', 'source_provider')) {
                $table->index(['tenant_id', 'source_provider']);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table): void {
                if (Schema::hasColumn('reviews', 'tenant_id') && Schema::hasColumn('reviews', 'source_provider')) {
                    $table->dropIndex(['tenant_id', 'source_provider']);
                }
            });
            Schema::table('reviews', function (Blueprint $table): void {
                foreach ([
                    'review_import_candidate_id',
                    'review_import_source_id',
                ] as $col) {
                    if (Schema::hasColumn('reviews', $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
                foreach (['imported_at', 'source_payload_json', 'source_original_body', 'source_url', 'source_external_id', 'source_provider'] as $col) {
                    if (Schema::hasColumn('reviews', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
            Schema::table('reviews', function (Blueprint $table): void {
                if (Schema::hasColumn('reviews', 'rating')) {
                    $table->unsignedTinyInteger('rating')->default(5)->nullable(false)->change();
                }
            });
        }

        Schema::dropIfExists('review_import_candidates');
        Schema::dropIfExists('review_import_runs');
        Schema::dropIfExists('review_import_sources');
    }
};
