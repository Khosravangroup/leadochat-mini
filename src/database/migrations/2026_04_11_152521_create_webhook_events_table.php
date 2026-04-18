<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('provider_connection_id')->nullable()->constrained('provider_connections')->nullOnDelete();
            $table->string('provider');
            $table->string('event_type')->nullable();
            $table->string('object')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->string('status')->default('received');
            $table->string('source')->default('webhook');
            $table->jsonb('headers')->nullable();
            $table->jsonb('payload');
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['workspace_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
