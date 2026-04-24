<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commerce_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 8)->default('USD');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->json('item_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_items');
    }
};
