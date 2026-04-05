<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_push_subscriptions')) {
            return;
        }

        Schema::table('notification_push_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_push_subscriptions', 'endpoint_hash')) {
                $table->string('endpoint_hash', 64)->nullable()->after('endpoint');
            }
        });

        $seen = [];
        foreach (DB::table('notification_push_subscriptions')->orderBy('id')->get() as $row) {
            $endpoint = (string) $row->endpoint;
            if ($endpoint === '') {
                DB::table('notification_push_subscriptions')->where('id', $row->id)->delete();

                continue;
            }
            $hash = hash('sha256', $endpoint);
            $key = $row->tenant_id.'|'.$row->user_id.'|'.$hash;
            if (isset($seen[$key])) {
                DB::table('notification_push_subscriptions')->where('id', $row->id)->delete();
            } else {
                $seen[$key] = true;
                DB::table('notification_push_subscriptions')->where('id', $row->id)->update(['endpoint_hash' => $hash]);
            }
        }

        Schema::table('notification_push_subscriptions', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'user_id', 'endpoint_hash'],
                'notif_push_sub_tenant_user_endpoint_hash_uq'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_push_subscriptions')) {
            return;
        }

        Schema::table('notification_push_subscriptions', function (Blueprint $table) {
            $table->dropUnique('notif_push_sub_tenant_user_endpoint_hash_uq');
        });

        if (Schema::hasColumn('notification_push_subscriptions', 'endpoint_hash')) {
            Schema::table('notification_push_subscriptions', function (Blueprint $table) {
                $table->dropColumn('endpoint_hash');
            });
        }
    }
};
