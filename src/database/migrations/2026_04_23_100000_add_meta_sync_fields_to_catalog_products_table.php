<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->string('meta_sync_status')->default('not_synced')->after('is_active');
            $table->text('meta_sync_error')->nullable()->after('meta_sync_status');
            $table->timestamp('meta_synced_at')->nullable()->after('meta_sync_error');

            $table->index(['catalog_id', 'meta_sync_status']);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->dropIndex(['catalog_id', 'meta_sync_status']);
            $table->dropColumn([
                'meta_sync_status',
                'meta_sync_error',
                'meta_synced_at',
            ]);
        });
    }
};
