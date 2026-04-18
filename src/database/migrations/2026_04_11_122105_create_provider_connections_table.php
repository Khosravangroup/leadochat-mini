<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_account_type');
            $table->string('provider_account_id');
            $table->string('provider_account_name')->nullable();
            $table->string('status')->default('disconnected');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_account_id']);
            $table->index(['workspace_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};
