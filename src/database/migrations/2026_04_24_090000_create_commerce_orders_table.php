<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('catalog_collection_id')->nullable()->constrained('catalog_collections')->nullOnDelete();
            $table->string('external_order_id')->nullable()->index();
            $table->string('external_checkout_id')->nullable();
            $table->string('source', 40)->default('manual_test')->index();
            $table->string('status', 40)->default('placed')->index();
            $table->string('payment_status', 40)->default('pending');
            $table->string('fulfillment_status', 40)->default('unfulfilled');
            $table->string('customer_reference')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('is_test')->default(true)->index();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('last_snapshot_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'provider_connection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_orders');
    }
};
