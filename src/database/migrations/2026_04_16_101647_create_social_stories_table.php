<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('instagram');
            $table->string('provider_story_id');
            $table->text('media_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 32)->default('active');
            $table->jsonb('raw')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_story_id'], 'social_stories_provider_story_unique');
            $table->index(['workspace_id', 'provider']);
            $table->index(['provider_connection_id', 'posted_at']);
            $table->index(['status', 'posted_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_stories');
    }
};
