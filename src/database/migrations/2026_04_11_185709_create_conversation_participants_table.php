<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->string('provider_user_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('handle')->nullable();
            $table->text('avatar_url')->nullable();
            $table->string('role')->default('participant');
            $table->boolean('is_self')->default(false);
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'is_self']);
            $table->index(['provider_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
