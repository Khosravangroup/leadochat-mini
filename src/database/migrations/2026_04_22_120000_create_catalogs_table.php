<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->nullable()->constrained('provider_connections')->nullOnDelete();
            $table->string('source')->default('leadochat');
            $table->string('external_catalog_id')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['provider_connection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};
