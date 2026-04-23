<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_product_id')->constrained('catalog_products')->cascadeOnDelete();
            $table->foreignId('catalog_product_market_override_id')->nullable()->constrained('catalog_product_market_overrides')->nullOnDelete();
            $table->string('name', 160);
            $table->string('status', 24)->default('draft');
            $table->string('discount_type', 24);
            $table->decimal('discount_value', 12, 2);
            $table->string('currency', 3)->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('checkout_url')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['catalog_product_id', 'status', 'priority'], 'catalog_product_offer_status_idx');
            $table->index(['catalog_product_market_override_id', 'status'], 'catalog_product_offer_market_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_offers');
    }
};
