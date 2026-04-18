<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained('provider_connections')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_conversation_id')->nullable();
            $table->string('type')->default('direct');
            $table->string('title')->nullable();
            $table->text('avatar_url')->nullable();
            $table->string('status')->default('active');
            $table->text('last_message_preview')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'provider']);
            $table->index(['provider_connection_id', 'last_message_at']);
            $table->unique(['provider', 'provider_conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
