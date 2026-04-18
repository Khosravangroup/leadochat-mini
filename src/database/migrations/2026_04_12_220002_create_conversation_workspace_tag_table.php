<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_workspace_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_tag_id')->constrained('workspace_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['conversation_id', 'workspace_tag_id'], 'conversation_workspace_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_workspace_tag');
    }
};
