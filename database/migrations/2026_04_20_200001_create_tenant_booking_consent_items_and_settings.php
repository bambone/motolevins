<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_booking_consent_items')) {
            Schema::create('tenant_booking_consent_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('code', 64);
                $table->string('label');
                $table->string('link_text')->nullable();
                $table->string('link_url', 2048)->nullable();
                $table->boolean('is_required')->default(true);
                $table->boolean('is_enabled')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
                $table->index(['tenant_id', 'is_enabled', 'sort_order'], 'tb_consent_tenant_enabled_sort_idx');
            });
        } else {
            $hasIdx = DB::select("SHOW INDEX FROM tenant_booking_consent_items WHERE Key_name = 'tb_consent_tenant_enabled_sort_idx'");
            if ($hasIdx === []) {
                Schema::table('tenant_booking_consent_items', function (Blueprint $table) {
                    $table->index(['tenant_id', 'is_enabled', 'sort_order'], 'tb_consent_tenant_enabled_sort_idx');
                });
            }
        }

        Tenant::query()->select('id')->orderBy('id')->chunkById(100, function ($tenants): void {
            foreach ($tenants as $tenant) {
                TenantSetting::setForTenant((int) $tenant->id, 'booking.legal_consents_required', false, 'boolean');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_booking_consent_items');
    }
};
