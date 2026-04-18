<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_connections', function (Blueprint $table) {
            $table->string('external_oauth_user_id')->nullable()->after('provider_account_id');
            $table->index(['provider', 'external_oauth_user_id'], 'provider_connections_provider_external_oauth_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('provider_connections', function (Blueprint $table) {
            $table->dropIndex('provider_connections_provider_external_oauth_user_id_index');
            $table->dropColumn('external_oauth_user_id');
        });
    }
};
