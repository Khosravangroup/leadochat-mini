<?php

namespace App\Services\Meta\Commerce;

use App\Models\ProviderConnection;
use Illuminate\Support\Arr;

class MetaCommerceReviewPacketService
{
    public function generateForConnection(ProviderConnection $connection, array $diagnostics): array
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];
        $commerceMeta = is_array($meta['meta_commerce'] ?? null) ? $meta['meta_commerce'] : [];
        $discovery = is_array($meta['meta_commerce_discovery'] ?? null) ? $meta['meta_commerce_discovery'] : [];
        $review = is_array($diagnostics['review'] ?? null) ? $diagnostics['review'] : [];
        $account = is_array($diagnostics['account'] ?? null) ? $diagnostics['account'] : [];
        $permissions = is_array($diagnostics['permissions'] ?? null) ? $diagnostics['permissions'] : [];
        $channel = is_array($diagnostics['channel'] ?? null) ? $diagnostics['channel'] : [];
        $webhook = is_array($diagnostics['webhook'] ?? null) ? $diagnostics['webhook'] : [];
        $shop = is_array($diagnostics['shop'] ?? null) ? $diagnostics['shop'] : [];
        $checkout = is_array($diagnostics['checkout_urls'] ?? null) ? $diagnostics['checkout_urls'] : [];
        $orderStats = is_array(Arr::get($shop, 'local_stats')) ? Arr::get($shop, 'local_stats') : [];
        $recentOrders = $connection->commerceOrders()
            ->with(['items', 'snapshots'])
            ->limit(5)
            ->get();
        $recentCampaigns = $connection->commercePromotionCampaigns()
            ->with(['collection.productSets', 'socialPost'])
            ->limit(5)
            ->get();

        $catalogs = collect($shop['meta_catalogs'] ?? [])
            ->filter(fn ($catalog) => is_array($catalog))
            ->values();

        $packet = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'provider' => 'instagram',
            'workspace_id' => $connection->workspace_id,
            'provider_connection_id' => $connection->id,
            'account' => [
                'provider_account_id' => $connection->provider_account_id,
                'provider_account_name' => $connection->provider_account_name,
                'username' => $account['username'] ?? null,
                'name' => $account['name'] ?? null,
                'ig_id' => $account['ig_id'] ?? null,
                'review_status' => $account['shopping_review_status'] ?? null,
                'product_tag_eligible' => (bool) ($account['shopping_product_tag_eligibility'] ?? false),
            ],
            'summary' => [
                'status' => $review['status'] ?? ($diagnostics['ok'] ?? false ? 'ready' : 'blocked'),
                'headline' => $review['headline'] ?? 'Meta commerce review packet generated.',
                'counts' => is_array($review['counts'] ?? null) ? $review['counts'] : [],
                'graph_version' => $diagnostics['graph_version'] ?? null,
            ],
            'permissions' => [
                'required' => array_values((array) ($permissions['required'] ?? [])),
                'granted' => array_values((array) ($permissions['granted'] ?? [])),
                'missing' => array_values((array) ($permissions['missing'] ?? [])),
                'without_demonstrated_api_journey' => array_values((array) ($permissions['without_demonstrated_api_journey'] ?? [])),
                'instagram_requested' => array_values((array) ($permissions['instagram_requested'] ?? [])),
                'instagram_without_demonstrated_api_journey' => array_values((array) ($permissions['instagram_without_demonstrated_api_journey'] ?? [])),
            ],
            'channel' => [
                'status' => $channel['status'] ?? null,
                'provider_account_type' => $channel['provider_account_type'] ?? null,
                'connected_at' => $channel['connected_at'] ?? null,
                'last_synced_at' => $channel['last_synced_at'] ?? null,
                'token_expires_at' => $channel['token_expires_at'] ?? null,
                'token_expired' => (bool) ($channel['token_expired'] ?? false),
                'live_subscribed_fields' => array_values((array) ($channel['live_subscribed_fields'] ?? [])),
                'live_subscribed_apps' => collect($channel['live_subscribed_apps'] ?? [])
                    ->filter(fn ($app) => is_array($app))
                    ->values()
                    ->all(),
            ],
            'webhook' => [
                'verified_fields' => array_values((array) ($webhook['verified_fields'] ?? [])),
                'missing_fields' => array_values((array) ($webhook['missing_fields'] ?? [])),
                'verified_at' => $webhook['verified_at'] ?? null,
            ],
            'catalogs' => [
                'business_ids' => array_values((array) ($commerceMeta['business_ids'] ?? [])),
                'discovered_count' => $catalogs->count(),
                'live_catalogs' => $catalogs
                    ->map(fn (array $catalog) => [
                        'name' => $catalog['name'] ?? null,
                        'external_catalog_id' => $catalog['external_catalog_id'] ?? null,
                        'vertical' => $catalog['vertical'] ?? null,
                        'product_count' => $catalog['product_count'] ?? null,
                        'live_ok' => (bool) ($catalog['live_ok'] ?? false),
                        'status' => $catalog['status'] ?? null,
                    ])
                    ->values()
                    ->all(),
            ],
            'local_shop' => [
                'source_catalog_count' => Arr::get($shop, 'local_stats.source_catalog_count', 0),
                'active_product_count' => Arr::get($shop, 'local_stats.active_product_count', 0),
                'market_override_count' => Arr::get($shop, 'local_stats.market_override_count', 0),
                'offer_count' => Arr::get($shop, 'local_stats.offer_count', 0),
                'product_set_count' => Arr::get($shop, 'local_stats.product_set_count', 0),
                'collection_count' => Arr::get($shop, 'local_stats.collection_count', 0),
            ],
            'checkout' => [
                'checked_count' => $checkout['checked_count'] ?? 0,
                'valid_count' => $checkout['valid_count'] ?? 0,
                'invalid_count' => $checkout['invalid_count'] ?? 0,
                'invalid_examples' => array_values((array) ($checkout['invalid_examples'] ?? [])),
            ],
            'orders' => [
                'order_count' => $orderStats['order_count'] ?? 0,
                'test_order_count' => $orderStats['test_order_count'] ?? 0,
                'snapshot_count' => $orderStats['order_snapshot_count'] ?? 0,
                'recent_orders' => $recentOrders->map(fn ($order) => [
                    'id' => $order->id,
                    'external_order_id' => $order->external_order_id,
                    'source' => $order->source,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'total_amount' => (float) $order->total_amount,
                    'currency' => $order->currency,
                    'is_test' => (bool) $order->is_test,
                    'placed_at' => optional($order->placed_at)->toIso8601String(),
                    'item_count' => $order->items->count(),
                    'snapshot_count' => $order->snapshots->count(),
                ])->values()->all(),
            ],
            'promotions' => [
                'campaign_count' => $orderStats['promotion_campaign_count'] ?? 0,
                'prepared_campaign_count' => $orderStats['prepared_promotion_campaign_count'] ?? 0,
                'promoted_post_campaign_count' => $orderStats['promoted_post_campaign_count'] ?? 0,
                'collection_ad_campaign_count' => $orderStats['collection_ad_campaign_count'] ?? 0,
                'shops_ad_campaign_count' => $orderStats['shops_ad_campaign_count'] ?? 0,
                'recent_campaigns' => $recentCampaigns->map(fn ($campaign) => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'campaign_type' => $campaign->campaign_type,
                    'status' => $campaign->status,
                    'objective' => $campaign->objective,
                    'meta_sync_status' => $campaign->meta_sync_status,
                    'collection_name' => $campaign->collection?->name,
                    'social_post_id' => $campaign->social_post_id,
                    'product_tag_count' => is_array($campaign->socialPost?->raw)
                        ? count((array) ($campaign->socialPost->raw['product_tags'] ?? []))
                        : 0,
                ])->values()->all(),
            ],
            'review_evidence' => [
                'items' => array_values((array) ($review['evidence'] ?? [])),
                'blockers' => array_values((array) ($review['blockers'] ?? [])),
                'warnings' => array_values((array) ($review['warnings'] ?? [])),
                'next_actions' => array_values((array) ($review['next_actions'] ?? [])),
            ],
            'demo_script' => $this->demoScript($connection, $diagnostics, $discovery),
            'review_notes' => $this->reviewNotes($diagnostics, $commerceMeta),
        ];

        return $packet;
    }

    public function exportFilename(ProviderConnection $connection, array $packet): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string) ($connection->provider_account_name ?: $connection->provider_account_id));
        $slug = trim((string) $slug, '-');
        $slug = $slug !== '' ? strtolower($slug) : 'instagram-account';
        $timestamp = now()->format('Ymd-His');

        return "meta-commerce-review-packet-{$slug}-{$timestamp}.json";
    }

    protected function demoScript(ProviderConnection $connection, array $diagnostics, array $discovery): array
    {
        $review = is_array($diagnostics['review'] ?? null) ? $diagnostics['review'] : [];
        $shop = is_array($diagnostics['shop'] ?? null) ? $diagnostics['shop'] : [];

        return [
            [
                'step' => 1,
                'title' => 'Open Commerce settings',
                'summary' => 'Open the workspace Commerce section and show the connected Instagram account.',
            ],
            [
                'step' => 2,
                'title' => 'Show the live readiness summary',
                'summary' => $review['headline'] ?? 'Review summary is available for the connected Instagram account.',
            ],
            [
                'step' => 3,
                'title' => 'Show discovered Meta assets',
                'summary' => 'Present the discovered Meta catalogs and live catalog access for account '.($connection->provider_account_id ?: 'unknown').'.',
            ],
            [
                'step' => 4,
                'title' => 'Show local shop structure',
                'summary' => 'Demonstrate product sync, product sets, collections, localized pricing, and active offers inside Leadochat.',
            ],
            [
                'step' => 5,
                'title' => 'Show live channel coverage',
                'summary' => 'Show required permissions, webhook fields, and token health to prove the connection is live and reviewable.',
            ],
            [
                'step' => 6,
                'title' => 'Show order proof',
                'summary' => 'Open the order ledger, show at least one test order, and open its latest snapshot to prove the order-state workflow.',
            ],
            [
                'step' => 7,
                'title' => 'Show ad and promotion groundwork',
                'summary' => 'Open the promotion campaigns list, show a prepared collection ad or promoted post payload preview, and explain how campaign assets map to synced commerce objects.',
            ],
            [
                'step' => 8,
                'title' => 'Show checkout handoff proof',
                'summary' => 'Demonstrate the HTTPS checkout URLs and explain how Instagram commerce traffic is handed off to the external store.',
            ],
            [
                'step' => 9,
                'title' => 'Reference the Meta discovery snapshot',
                'summary' => count((array) ($discovery['catalogs'] ?? [])) > 0
                    ? 'The saved discovery snapshot includes '.count((array) ($discovery['catalogs'] ?? [])).' discovered Meta catalog(s).'
                    : 'Run discovery once more if you need to refresh the saved asset snapshot before submission.',
            ],
        ];
    }

    protected function reviewNotes(array $diagnostics, array $commerceMeta): array
    {
        $review = is_array($diagnostics['review'] ?? null) ? $diagnostics['review'] : [];
        $permissions = is_array($diagnostics['permissions'] ?? null) ? $diagnostics['permissions'] : [];

        return [
            'requested_scopes' => array_values((array) ($commerceMeta['review_scopes'] ?? $permissions['required'] ?? [])),
            'recommended_submission_focus' => [
                'Commerce asset discovery',
                'Live permission coverage',
                'Webhook subscription coverage',
                'Product tagging eligibility',
                'Catalog structure with product sets and collections',
                'Test order flows and order snapshots',
                'Collection ads, shops ads, and promoted post groundwork',
                'Localized products, offers, and checkout flow links',
            ],
            'status_message' => $review['headline'] ?? null,
        ];
    }
}
