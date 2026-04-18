<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('provider_connections', 'external_oauth_user_id')) {
            Schema::table('provider_connections', function (Blueprint $table) {
                $table->string('external_oauth_user_id')->nullable()->after('provider_account_id');
            });
        }

        $indexExists = DB::selectOne("
            SELECT 1
            FROM pg_indexes
            WHERE schemaname = 'public'
              AND tablename = 'provider_connections'
              AND indexname = 'provider_connections_provider_external_oauth_user_id_index'
            LIMIT 1
        ");

        if (!$indexExists) {
            Schema::table('provider_connections', function (Blueprint $table) {
                $table->index(
                    ['provider', 'external_oauth_user_id'],
                    'provider_connections_provider_external_oauth_user_id_index'
                );
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::selectOne("
            SELECT 1
            FROM pg_indexes
            WHERE schemaname = 'public'
              AND tablename = 'provider_connections'
              AND indexname = 'provider_connections_provider_external_oauth_user_id_index'
            LIMIT 1
        ");

        if ($indexExists) {
            Schema::table('provider_connections', function (Blueprint $table) {
                $table->dropIndex('provider_connections_provider_external_oauth_user_id_index');
            });
        }
    }
};;
