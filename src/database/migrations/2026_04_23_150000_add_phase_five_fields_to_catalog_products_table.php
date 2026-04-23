<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->string('brand', 120)->nullable();
            $table->string('product_condition', 32)->nullable();
            $table->unsignedInteger('inventory_quantity')->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamp('sale_price_effective_start_at')->nullable();
            $table->timestamp('sale_price_effective_end_at')->nullable();
            $table->string('google_product_category', 255)->nullable();
            $table->string('content_language', 12)->nullable();
            $table->string('target_country', 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table) {
            $table->dropColumn([
                'brand',
                'product_condition',
                'inventory_quantity',
                'sale_price',
                'sale_price_effective_start_at',
                'sale_price_effective_end_at',
                'google_product_category',
                'content_language',
                'target_country',
            ]);
        });
    }
};
