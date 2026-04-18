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
        Schema::create('social_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('instagram');
            $table->string('provider_media_id');
            $table->string('parent_provider_media_id')->nullable();
            $table->string('media_type', 50)->nullable();
            $table->text('media_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_media_id'], 'social_post_media_provider_media_unique');
            $table->index(['social_post_id', 'position']);
            $table->index(['workspace_id', 'provider']);
            $table->index(['provider_connection_id', 'posted_at']);
            $table->index(['parent_provider_media_id']);
            $table->index(['is_cover']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_post_media');
    }
};
