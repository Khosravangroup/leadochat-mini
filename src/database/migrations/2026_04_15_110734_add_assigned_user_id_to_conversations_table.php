<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('department_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['workspace_id', 'assigned_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'assigned_user_id']);
            $table->dropConstrainedForeignId('assigned_user_id');
        });
    }
};
