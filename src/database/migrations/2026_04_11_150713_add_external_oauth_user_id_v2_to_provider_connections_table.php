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

        $indexExists = $this->providerConnectionsIndexExists();

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
        $indexExists = $this->providerConnectionsIndexExists();

        if ($indexExists) {
            Schema::table('provider_connections', function (Blueprint $table) {
                $table->dropIndex('provider_connections_provider_external_oauth_user_id_index');
            });
        }
    }

    protected function providerConnectionsIndexExists(): bool
    {
        $indexName = 'provider_connections_provider_external_oauth_user_id_index';
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne("
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = 'public'
                  AND tablename = 'provider_connections'
                  AND indexname = ?
                LIMIT 1
            ", [$indexName]);
        }

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('provider_connections')") as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        try {
            return Schema::hasIndex('provider_connections', $indexName);
        } catch (\Throwable) {
            return false;
        }
    }
};
