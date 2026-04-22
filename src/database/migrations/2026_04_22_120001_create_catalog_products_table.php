<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->string('external_product_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('image_url')->nullable();
            $table->text('product_url')->nullable();
            $table->string('availability')->default('in_stock');
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['catalog_id', 'is_active']);
            $table->index(['catalog_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
