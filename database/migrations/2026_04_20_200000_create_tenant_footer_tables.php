<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_footer_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('section_key')->nullable();
            $table->string('type', 64);
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('meta_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->string('theme_scope')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'section_key']);
            $table->index(['tenant_id', 'is_enabled', 'sort_order'], 'tfoot_sect_tenant_en_sort_idx');
        });

        Schema::create('tenant_footer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('tenant_footer_sections')->cascadeOnDelete();
            $table->string('group_key')->nullable();
            $table->string('label');
            $table->string('url', 2048);
            $table->string('link_kind', 32);
            $table->string('target', 16)->nullable();
            $table->string('icon_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'is_enabled', 'sort_order'], 'tfoot_link_sect_en_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_footer_links');
        Schema::dropIfExists('tenant_footer_sections');
    }
};
