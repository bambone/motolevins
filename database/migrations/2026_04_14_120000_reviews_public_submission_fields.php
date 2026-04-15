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

        Schema::table('reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('reviews', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('updated_at');
            }
            if (! Schema::hasColumn('reviews', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('reviews', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('moderated_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('reviews', 'moderation_note')) {
                $table->text('moderation_note')->nullable()->after('moderated_by');
            }
            if (! Schema::hasColumn('reviews', 'contact_email')) {
                $table->string('contact_email', 255)->nullable()->after('city');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reviews')) {
            return;
        }

        Schema::table('reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('reviews', 'contact_email')) {
                $table->dropColumn('contact_email');
            }
            if (Schema::hasColumn('reviews', 'moderation_note')) {
                $table->dropColumn('moderation_note');
            }
            if (Schema::hasColumn('reviews', 'moderated_by')) {
                $table->dropConstrainedForeignId('moderated_by');
            }
            if (Schema::hasColumn('reviews', 'moderated_at')) {
                $table->dropColumn('moderated_at');
            }
            if (Schema::hasColumn('reviews', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};
