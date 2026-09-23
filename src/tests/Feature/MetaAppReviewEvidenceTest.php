<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\CommerceOrder;
use App\Models\CommercePromotionCampaign;
use App\Models\Conversation;
use App\Models\ConversationProductShare;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\ProviderPermission;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\SocialStory;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaAppReviewEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_instagram_review_evidence_does_not_call_deferred_commerce_apis(): void
    {
        config([
            'services.meta.commerce_review_scopes' => '',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish,instagram_business_manage_insights',
        ]);

        Http::fake();

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Instagram-only Review Workspace',
            'slug' => 'instagram-only-review-workspace',
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => '17841439881430000',
            'provider_account_name' => 'instagram_review_account',
            'status' => 'connected',
            'connected_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('settings.commerce.app-review-evidence.generate', $connection))
            ->assertRedirect(route('settings.index', ['section' => 'commerce']));

        Http::assertNothingSent();

        $packet = $connection->refresh()->meta['meta_app_review_evidence'];

        $this->assertSame('needs_attention', $packet['summary']['status']);
        $this->assertSame([], $packet['evidence']['blockers']);
        $this->assertSame([
            'instagram_business_basic',
            'instagram_business_manage_messages',
            'instagram_business_manage_comments',
            'instagram_business_content_publish',
            'instagram_business_manage_insights',
        ], $packet['review_notes']['requested_scopes']);
        $this->assertArrayNotHasKey('commerce', $packet['coverage']);
        $this->assertArrayNotHasKey('commerce_review_packet', $packet);
        $this->assertArrayNotHasKey('meta_commerce_review_packet', $connection->meta);
    }

    public function test_it_generates_and_downloads_the_final_app_review_evidence_packet(): void
    {
        config([
            'services.meta.graph_version' => 'v25.0',
            'services.meta.commerce_review_scopes' => 'business_management,catalog_management,instagram_business_basic',
            'services.instagram.scopes' => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish',
        ]);

        Http::fake([
            'https://graph.facebook.com/v25.0/instagram-account-evidence?*' => Http::response([
                'id' => 'instagram-account-evidence',
                'username' => 'evidence_shop',
                'name' => 'Evidence Shop',
                'ig_id' => '17841439881436376',
                'shopping_product_tag_eligibility' => true,
                'shopping_review_status' => 'approved',
            ], 200),
            'https://graph.facebook.com/v25.0/me/permissions*' => Http::response([
                'data' => [
                    ['permission' => 'business_management', 'status' => 'granted'],
                    ['permission' => 'catalog_management', 'status' => 'granted'],
                    ['permission' => 'instagram_business_basic', 'status' => 'granted'],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/instagram-account-evidence/subscribed_apps*' => Http::response([
                'data' => [
                    [
                        'id' => 'app-123',
                        'name' => 'Leadochat Mini',
                        'subscribed_fields' => ['messages', 'comments', 'message_reactions'],
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v25.0/meta-catalog-evidence*' => Http::response([
                'id' => 'meta-catalog-evidence',
                'name' => 'Evidence Catalog',
                'vertical' => 'commerce',
                'product_count' => 12,
            ], 200),
        ]);

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Meta App Review Evidence Workspace',
            'slug' => 'meta-app-review-evidence-workspace',
        ]);

        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'instagram-account-evidence',
            'provider_account_name' => 'evidence_shop',
            'status' => 'connected',
            'connected_at' => now()->subDay(),
            'meta' => [
                'webhook_subscription' => [
                    'success' => true,
                    'verified_fields' => ['messages', 'comments', 'message_reactions'],
                    'verified_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'test-meta-access-token',
            'expires_at' => now()->addDay(),
            'is_primary' => true,
        ]);

        foreach (['business_management', 'catalog_management', 'instagram_business_basic'] as $permission) {
            ProviderPermission::create([
                'provider_connection_id' => $connection->id,
                'permission' => $permission,
                'status' => 'granted',
            ]);
        }

        $sourceCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'leadochat',
            'name' => 'Evidence Source Catalog',
            'status' => 'active',
        ]);

        $product = CatalogProduct::create([
            'catalog_id' => $sourceCatalog->id,
            'sku' => 'EVIDENCE-001',
            'title' => 'Evidence Product',
            'price' => 55,
            'currency' => 'USD',
            'product_url' => 'https://example.com/products/evidence',
            'availability' => 'in_stock',
            'is_active' => true,
            'meta_sync_status' => 'synced',
        ]);

        $product->marketOverrides()->create([
            'target_country' => 'AE',
            'content_language' => 'fa_IR',
            'price' => 210,
            'currency' => 'AED',
            'checkout_url' => 'https://checkout.example.com/ae/evidence',
            'is_active' => true,
        ]);

        $product->offers()->create([
            'name' => 'Evidence Offer',
            'status' => 'active',
            'discount_type' => 'fixed_amount',
            'discount_value' => 5,
            'currency' => 'USD',
            'priority' => 1,
            'checkout_url' => 'https://checkout.example.com/offer/evidence',
        ]);

        $metaCatalog = Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'source' => 'meta',
            'external_catalog_id' => 'meta-catalog-evidence',
            'name' => 'Evidence Meta Catalog',
            'status' => 'active',
        ]);

        $productSet = CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Evidence Set',
            'status' => 'active',
            'meta_sync_status' => 'synced',
        ]);

        $productSet->products()->sync([$product->id => ['sort_order' => 0]]);

        $collection = CatalogCollection::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $metaCatalog->id,
            'name' => 'Evidence Collection',
            'status' => 'active',
            'meta_sync_status' => 'synced',
        ]);

        $collection->productSets()->sync([$productSet->id => ['sort_order' => 0]]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_conversation_id' => 'ig-conversation-1',
            'type' => 'instagram_dm',
            'title' => 'Evidence Conversation',
            'status' => 'open',
            'last_message_preview' => 'Need more info about this product.',
            'unread_count' => 1,
            'last_message_at' => now(),
        ]);

        $customer = $conversation->participants()->create([
            'provider_user_id' => 'customer-1',
            'display_name' => 'Evidence Customer',
            'handle' => '@evidence.customer',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'role' => 'customer',
            'is_self' => false,
        ]);

        $agent = $conversation->participants()->create([
            'provider_user_id' => 'self-1',
            'display_name' => 'Evidence Agent',
            'handle' => '@evidence.shop',
            'avatar_url' => 'https://example.com/agent.jpg',
            'role' => 'agent',
            'is_self' => true,
        ]);

        $inboundMessage = $conversation->messages()->create([
            'sender_participant_id' => $customer->id,
            'provider' => 'instagram',
            'provider_message_id' => 'ig-message-1',
            'direction' => 'inbound',
            'message_type' => 'image',
            'text_body' => 'Need more info about this product.',
            'status' => 'received',
            'received_at' => now()->subMinutes(8),
            'meta' => [
                'story_id' => 'ig-story-1',
                'customer_reaction' => ['reaction' => 'love'],
            ],
        ]);

        $inboundMessage->attachments()->create([
            'attachment_type' => 'image',
            'url' => 'https://example.com/attachment.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $outboundMessage = $conversation->messages()->create([
            'sender_participant_id' => $agent->id,
            'provider' => 'instagram',
            'provider_message_id' => 'ig-message-2',
            'direction' => 'outbound',
            'message_type' => 'catalog_product',
            'text_body' => 'Here is the product card and more context.',
            'status' => 'sent',
            'sent_at' => now()->subMinutes(3),
            'meta' => [
                'source_social_comment_id' => 1,
                'source_provider_comment_id' => 'ig-comment-1',
                'agent_reaction' => ['reaction' => 'love'],
            ],
        ]);

        $outboundMessage->attachments()->create([
            'attachment_type' => 'image',
            'url' => 'https://example.com/product-card.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $conversation->refresh();
        $conversation->last_message_preview = 'Here is the product card and more context.';
        $conversation->last_message_at = now()->subMinutes(3);
        $conversation->save();

        ConversationProductShare::create([
            'conversation_id' => $conversation->id,
            'message_id' => $outboundMessage->id,
            'catalog_product_id' => $product->id,
            'agent_id' => $user->id,
            'product_snapshot' => [
                'title' => $product->title,
                'sku' => $product->sku,
            ],
        ]);

        $socialPost = SocialPost::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_media_id' => 'ig-post-evidence',
            'media_type' => 'IMAGE',
            'caption' => 'Evidence post with product tags',
            'permalink' => 'https://instagram.com/p/evidence',
            'comments_count' => 1,
            'like_count' => 8,
            'status' => 'active',
            'posted_at' => now()->subHours(3),
            'raw' => [
                'product_tags' => [
                    ['product_id' => 'EVIDENCE-001', 'x' => 0.5, 'y' => 0.5],
                ],
            ],
        ]);

        SocialComment::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'social_post_id' => $socialPost->id,
            'provider' => 'instagram',
            'provider_media_id' => $socialPost->provider_media_id,
            'provider_comment_id' => 'ig-comment-1',
            'provider_user_id' => 'customer-1',
            'username' => 'evidence.customer',
            'text' => 'Can you send this in DM?',
            'status' => 'active',
            'is_hidden' => false,
            'commented_at' => now()->subHours(2),
            'replied_publicly_at' => now()->subHours(2)->addMinutes(2),
            'replied_via_dm_at' => now()->subHours(2)->addMinutes(5),
        ]);

        SocialStory::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'provider_story_id' => 'ig-story-1',
            'media_url' => 'https://example.com/story.mp4',
            'posted_at' => now()->subHours(1),
            'expires_at' => now()->addHours(23),
            'status' => 'published',
            'raw' => [
                'media_type' => 'VIDEO',
            ],
        ]);

        $order = CommerceOrder::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_id' => $sourceCatalog->id,
            'catalog_collection_id' => $collection->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'fulfillment_status' => 'processing',
            'source' => 'manual_test',
            'currency' => 'USD',
            'subtotal_amount' => 55,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 55,
            'is_test' => true,
            'placed_at' => now()->subMinutes(40),
        ]);

        $order->items()->create([
            'catalog_product_id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'quantity' => 1,
            'currency' => 'USD',
            'unit_price' => 55,
            'total_price' => 55,
            'item_snapshot' => ['title' => $product->title],
        ]);

        $order->snapshots()->create([
            'snapshot_type' => 'review_demo',
            'status' => 'processing',
            'captured_at' => now()->subMinutes(35),
            'payload' => ['note' => 'Snapshot for app review evidence.'],
        ]);

        CommercePromotionCampaign::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_collection_id' => $collection->id,
            'social_post_id' => $socialPost->id,
            'campaign_type' => 'promoted_post',
            'objective' => 'sales',
            'name' => 'Evidence Promotion Campaign',
            'status' => 'active',
            'destination_url' => 'https://example.com/shop/evidence',
            'budget_amount' => 120,
            'currency' => 'USD',
            'meta_sync_status' => 'prepared',
        ]);

        WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'messages',
            'object' => 'instagram',
            'provider_event_id' => 'event-1',
            'status' => 'processed',
            'source' => 'instagram_webhook',
            'payload' => ['field' => 'messages'],
            'processed_at' => now()->subMinutes(10),
        ]);

        WebhookEvent::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'instagram',
            'event_type' => 'comments',
            'object' => 'instagram',
            'provider_event_id' => 'event-2',
            'status' => 'processed',
            'source' => 'instagram_webhook',
            'payload' => ['field' => 'comments'],
            'processed_at' => now()->subMinutes(6),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.commerce.app-review-evidence.generate', $connection));

        $response->assertRedirect(route('settings.index', ['section' => 'commerce']));

        $connection->refresh();

        $packet = $connection->meta['meta_app_review_evidence'];

        $this->assertSame('ready', $packet['summary']['status']);
        $this->assertSame('evidence_shop', $packet['account']['username']);
        $this->assertSame(1, data_get($packet, 'coverage.inbox.conversation_count'));
        $this->assertSame(2, data_get($packet, 'coverage.inbox.message_count'));
        $this->assertSame(1, data_get($packet, 'coverage.social.post_count'));
        $this->assertSame(1, data_get($packet, 'coverage.social.comment_count'));
        $this->assertSame(1, data_get($packet, 'coverage.social.story_count'));
        $this->assertSame(2, data_get($packet, 'coverage.webhooks.event_count'));
        $this->assertCount(8, $packet['demo_script']);
        $this->assertCount(1, $connection->meta['meta_app_review_evidence_history']);
        $this->assertSame('ready', data_get($connection->meta, 'meta_commerce_review_packet.summary.status'));

        $download = $this
            ->actingAs($user)
            ->get(route('settings.commerce.app-review-evidence.download', $connection));

        $download->assertOk();
        $download->assertHeader('content-type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $download->headers->get('content-disposition'));

        $downloadedPacket = json_decode($download->streamedContent(), true);
        $downloadedJson = json_encode($downloadedPacket, JSON_THROW_ON_ERROR);

        $this->assertSame('ready', data_get($downloadedPacket, 'summary.status'));
        $this->assertSame(1, data_get($downloadedPacket, 'coverage.social.dm_reply_count'));
        $this->assertSame(2, data_get($downloadedPacket, 'coverage.webhooks.processed_count'));
        $this->assertArrayNotHasKey('recent_activity', $downloadedPacket);
        $this->assertArrayNotHasKey('recent_conversations', $downloadedPacket['coverage']['inbox']);
        $this->assertArrayNotHasKey('recent_messages', $downloadedPacket['coverage']['inbox']);
        $this->assertArrayNotHasKey('recent_posts', $downloadedPacket['coverage']['social']);
        $this->assertArrayNotHasKey('recent_comments', $downloadedPacket['coverage']['social']);
        $this->assertArrayNotHasKey('recent_events', $downloadedPacket['coverage']['webhooks']);

        foreach ([
            'Evidence Customer',
            'Need more info about this product.',
            'Can you send this in DM?',
            'event-1',
            'Evidence Promotion Campaign',
        ] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $downloadedJson);
        }
    }
}
