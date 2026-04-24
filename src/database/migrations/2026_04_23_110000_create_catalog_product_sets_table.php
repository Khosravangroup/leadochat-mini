<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->nullable()->constrained('provider_connections')->nullOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->string('external_product_set_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('meta_sync_status')->default('not_synced');
            $table->text('meta_sync_error')->nullable();
            $table->timestamp('meta_synced_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['catalog_id', 'status']);
            $table->index(['provider_connection_id', 'meta_sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_sets');
    }
};
