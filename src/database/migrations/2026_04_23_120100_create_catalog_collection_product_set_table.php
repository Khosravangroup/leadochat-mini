<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_collection_product_set', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_collection_id')->constrained('catalog_collections')->cascadeOnDelete();
            $table->foreignId('catalog_product_set_id')->constrained('catalog_product_sets')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_collection_id', 'catalog_product_set_id'], 'catalog_collection_product_set_unique');
            $table->index(['catalog_product_set_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_collection_product_set');
    }
};
