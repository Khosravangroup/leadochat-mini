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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('instagram');
            $table->string('provider_media_id');
            $table->string('media_type', 50)->nullable();
            $table->text('caption')->nullable();
            $table->text('permalink')->nullable();
            $table->text('media_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->string('status', 32)->default('published');
            $table->jsonb('raw')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_media_id'], 'social_posts_provider_media_unique');
            $table->index(['workspace_id', 'provider']);
            $table->index(['provider_connection_id', 'posted_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
