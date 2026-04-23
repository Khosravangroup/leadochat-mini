<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commerce_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('snapshot_type', 40)->default('manual')->index();
            $table->string('status', 40)->nullable();
            $table->timestamp('captured_at')->nullable()->index();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_snapshots');
    }
};
