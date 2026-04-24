<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_market_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->string('target_country', 2);
            $table->string('content_language', 12);
            $table->string('title', 180)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->text('product_url')->nullable();
            $table->text('checkout_url')->nullable();
            $table->string('google_product_category', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['catalog_product_id', 'target_country', 'content_language'], 'catalog_product_market_unique');
            $table->index(['catalog_product_id', 'is_active'], 'catalog_product_market_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_market_overrides');
    }
};
