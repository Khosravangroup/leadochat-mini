<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('workspace_departments')
                ->nullOnDelete();

            $table->index(['workspace_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'department_id']);
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
