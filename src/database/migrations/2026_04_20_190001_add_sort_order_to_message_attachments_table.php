<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('message_attachments', 'sort_order')) {
            return;
        }

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['message_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('message_attachments', 'sort_order')) {
            return;
        }

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropIndex(['message_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
