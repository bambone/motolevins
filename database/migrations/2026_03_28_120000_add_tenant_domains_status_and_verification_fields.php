<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_domains', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_domains', 'status')) {
                $table->string('status', 20)->default('pending')->index();
            }
            if (! Schema::hasColumn('tenant_domains', 'verification_method')) {
                $table->string('verification_method', 32)->nullable();
            }
            if (! Schema::hasColumn('tenant_domains', 'verification_token')) {
                $table->string('verification_token', 128)->nullable()->index();
            }
            if (! Schema::hasColumn('tenant_domains', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }
            if (! Schema::hasColumn('tenant_domains', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (! Schema::hasColumn('tenant_domains', 'activated_at')) {
                $table->timestamp('activated_at')->nullable();
            }
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $rows = DB::table('tenant_domains')->get();

        foreach ($rows as $row) {
            $status = $this->mapStatus($row);
            $ssl = $this->mapSslStatus($row);

            DB::table('tenant_domains')->where('id', $row->id)->update([
                'status' => $status,
                'ssl_status' => $ssl,
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    private function mapStatus(object $row): string
    {
        $v = $row->verification_status ?? null;
        $type = $row->type ?? 'subdomain';

        if ($type === 'subdomain') {
            if ($v === null || $v === '' || $v === 'verified') {
                return 'active';
            }

            return match ($v) {
                'pending' => 'pending',
                'verifying' => 'verifying',
                'failed' => 'failed',
                default => 'active',
            };
        }

        return match ($v) {
            'verified' => 'active',
            'pending' => 'pending',
            'verifying' => 'verifying',
            'failed' => 'failed',
            null, '' => 'pending',
            default => 'pending',
        };
    }

    private function mapSslStatus(object $row): string
    {
        $type = $row->type ?? 'subdomain';
        $old = $row->ssl_status ?? null;

        if ($type === 'subdomain') {
            return 'not_required';
        }

        return match ($old) {
            'active' => 'issued',
            'error' => 'failed',
            'issuing', 'pending' => 'pending',
            null, '' => 'pending',
            default => 'pending',
        };
    }

    public function down(): void
    {
        Schema::table('tenant_domains', function (Blueprint $table) {
            foreach (['status', 'verification_method', 'verification_token', 'last_checked_at', 'verified_at', 'activated_at'] as $column) {
                if (Schema::hasColumn('tenant_domains', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
