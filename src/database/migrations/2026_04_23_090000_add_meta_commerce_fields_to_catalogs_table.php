<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->string('external_business_id')->nullable()->after('external_catalog_id');
            $table->string('external_commerce_account_id')->nullable()->after('external_business_id');
            $table->string('meta_sync_status')->default('not_synced')->after('status');
            $table->text('meta_sync_error')->nullable()->after('meta_sync_status');
            $table->timestamp('meta_synced_at')->nullable()->after('last_synced_at');

            $table->index(['external_business_id', 'status']);
            $table->index(['external_catalog_id', 'provider_connection_id']);
        });
    }

    public function down(): void
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->dropIndex(['external_business_id', 'status']);
            $table->dropIndex(['external_catalog_id', 'provider_connection_id']);
            $table->dropColumn([
                'external_business_id',
                'external_commerce_account_id',
                'meta_sync_status',
                'meta_sync_error',
                'meta_synced_at',
            ]);
        });
    }
};
