<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('attachment_type')->default('file');
            $table->text('url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'attachment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
