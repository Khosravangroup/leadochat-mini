<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_promotion_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_collection_id')->nullable()->constrained('catalog_collections')->nullOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->string('campaign_type', 40)->index();
            $table->string('objective', 40)->default('sales');
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->string('call_to_action', 60)->nullable();
            $table->string('destination_url')->nullable();
            $table->string('external_campaign_id')->nullable()->index();
            $table->string('external_ad_set_id')->nullable();
            $table->string('external_ad_id')->nullable();
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('meta_sync_status', 40)->default('not_prepared')->index();
            $table->text('meta_sync_error')->nullable();
            $table->timestamp('meta_synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_promotion_campaigns');
    }
};
