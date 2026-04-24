<?php

namespace App\Services\Meta\Commerce;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\CommerceOrder;
use App\Models\CommercePromotionCampaign;
use App\Models\OauthToken;
use App\Models\ProviderConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaCommerceDiagnosticsService
{
    public function diagnoseForConnection(ProviderConnection $connection): array
    {
        if ($connection->provider !== 'instagram') {
            throw new RuntimeException('Meta Commerce diagnostics require an Instagram provider connection.');
        }

        $accessToken = $this->resolveAccessToken($connection);
        $graphVersion = $this->resolveGraphVersion();
        $accountId = (string) $connection->provider_account_id;

        $checks = [];
        $checks['instagram_account'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/{$accountId}",
            [
                'fields' => 'id,username,name,ig_id,shopping_product_tag_eligibility,shopping_review_status',
            ]
        );

        $checks['granted_permissions'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/me/permissions",
            [
                'limit' => 200,
            ]
        );

        $checks['subscribed_apps'] = $this->requestJson(
            $accessToken,
            "https://graph.facebook.com/{$graphVersion}/{$accountId}/subscribed_apps",
            [
                'fields' => 'id,name,subscribed_fields',
                'limit' => 50,
            ]
        );

        $grantedPermissions = $this->extractGrantedPermissions($checks['granted_permissions']);
        $requiredPermissions = $this->requiredReviewScopes();
        $missingPermissions = array_values(array_diff($requiredPermissions, $grantedPermissions));

        $workspace = $connection->workspace;
        $sourceCatalogs = Catalog::query()
            ->where('workspace_id', $workspace?->id)
            ->where('source', '!=', 'meta')
            ->with(['products.marketOverrides', 'products.offers'])
            ->get();

        $metaCatalogs = Catalog::query()
            ->where('workspace_id', $workspace?->id)
            ->where('source', 'meta')
            ->where('provider_connection_id', $connection->id)
            ->get();

        foreach ($metaCatalogs as $metaCatalog) {
            $externalCatalogId = trim((string) $metaCatalog->external_catalog_id);

            if ($externalCatalogId === '') {
                continue;
            }

            $checks["catalog_detail:{$externalCatalogId}"] = $this->requestJson(
                $accessToken,
                "https://graph.facebook.com/{$graphVersion}/{$externalCatalogId}",
                [
                    'fields' => 'id,name,vertical,product_count',
                ]
            );
        }

        $products = $sourceCatalogs
            ->flatMap(fn (Catalog $catalog) => $catalog->products)
            ->values();

        $checkoutSummary = $this->checkoutSummary($products);
        $accountBody = is_array($checks['instagram_account']['body'] ?? null) ? $checks['instagram_account']['body'] : [];
        $webhookMeta = is_array($connection->meta ?? null) ? ($connection->meta['webhook_subscription'] ?? []) : [];
        $liveWebhookFields = collect(Arr::get($checks['subscribed_apps'], 'body.data', []))
            ->filter(fn ($subscription) => is_array($subscription))
            ->flatMap(fn ($subscription) => Arr::get($subscription, 'subscribed_fields', []))
            ->map(fn ($field) => (string) $field)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $verifiedWebhookFields = collect($liveWebhookFields !== [] ? $liveWebhookFields : ($webhookMeta['verified_fields'] ?? []))
            ->map(fn ($field) => (string) $field)
            ->filter()
            ->values()
            ->all();
        $requiredWebhookFields = ['messages', 'comments'];
        $missingWebhookFields = array_values(array_diff($requiredWebhookFields, $verifiedWebhookFields));
        $token = $this->primaryToken($connection);
        $tokenExpiresAt = $token?->expires_at;
        $tokenExpired = $tokenExpiresAt ? $tokenExpiresAt->isPast() : false;

        $localStats = [
            'source_catalog_count' => $sourceCatalogs->count(),
            'meta_catalog_count' => $metaCatalogs->count(),
            'active_product_count' => $products->where('is_active', true)->count(),
            'market_override_count' => $products->sum(fn (CatalogProduct $product) => $product->marketOverrides->count()),
            'offer_count' => $products->sum(fn (CatalogProduct $product) => $product->offers->count()),
            'product_set_count' => $connection->catalogProductSets()->count(),
            'collection_count' => $connection->catalogCollections()->count(),
            'order_count' => CommerceOrder::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->count(),
            'test_order_count' => CommerceOrder::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->where('is_test', true)
                ->count(),
            'order_snapshot_count' => CommerceOrder::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->withCount('snapshots')
                ->get()
                ->sum('snapshots_count'),
            'promotion_campaign_count' => CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->count(),
            'prepared_promotion_campaign_count' => CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->whereIn('meta_sync_status', ['prepared', 'prepared_with_warnings'])
                ->count(),
            'promoted_post_campaign_count' => CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->where('campaign_type', 'promoted_post')
                ->count(),
            'collection_ad_campaign_count' => CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->where('campaign_type', 'collection_ad')
                ->count(),
            'shops_ad_campaign_count' => CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace?->id)
                ->where('provider_connection_id', $connection->id)
                ->where('campaign_type', 'shops_ad')
                ->count(),
        ];

        $liveCatalogs = $metaCatalogs->map(function (Catalog $catalog) use ($checks): array {
            $externalCatalogId = (string) $catalog->external_catalog_id;
            $check = $checks["catalog_detail:{$externalCatalogId}"] ?? null;
            $body = is_array($check['body'] ?? null) ? $check['body'] : [];

            return [
                'id' => $catalog->id,
                'external_catalog_id' => $catalog->external_catalog_id,
                'name' => $body['name'] ?? $catalog->name,
                'vertical' => $body['vertical'] ?? data_get($catalog->meta, 'meta_catalog.vertical'),
                'product_count' => $body['product_count'] ?? data_get($catalog->meta, 'meta_catalog.product_count'),
                'status' => $catalog->meta_sync_status,
                'live_ok' => (bool) ($check['ok'] ?? false),
                'live_error' => $check['error'] ?? null,
            ];
        })->values()->all();

        $readiness = [
            $this->makeReadinessCheck(
                'channel_health',
                ! $tokenExpired ? 'ok' : 'fail',
                ! $tokenExpired
                    ? 'Primary access token is present and not expired.'
                    : 'Primary access token is expired and must be refreshed by reconnecting the channel.',
                [
                    'connected_at' => optional($connection->connected_at)?->toIso8601String(),
                    'last_synced_at' => optional($connection->last_synced_at)?->toIso8601String(),
                    'token_expires_at' => optional($tokenExpiresAt)?->toIso8601String(),
                    'token_expired' => $tokenExpired,
                ]
            ),
            $this->makeReadinessCheck(
                'permissions',
                count($missingPermissions) === 0 ? 'ok' : 'fail',
                count($missingPermissions) === 0
                    ? 'All configured commerce review permissions are granted.'
                    : 'Missing required review permissions: ' . implode(', ', $missingPermissions),
                [
                    'required' => $requiredPermissions,
                    'granted' => $grantedPermissions,
                    'missing' => $missingPermissions,
                ]
            ),
            $this->makeReadinessCheck(
                'product_tag_eligibility',
                !empty($accountBody['shopping_product_tag_eligibility']) ? 'ok' : 'warn',
                !empty($accountBody['shopping_product_tag_eligibility'])
                    ? 'Instagram account is eligible for product tagging.'
                    : 'Instagram account did not report product-tag eligibility yet.',
                [
                    'shopping_product_tag_eligibility' => (bool) ($accountBody['shopping_product_tag_eligibility'] ?? false),
                    'shopping_review_status' => $accountBody['shopping_review_status'] ?? null,
                ]
            ),
            $this->makeReadinessCheck(
                'webhook_subscription',
                count($missingWebhookFields) === 0 ? 'ok' : 'warn',
                count($missingWebhookFields) === 0
                    ? 'Webhook subscription includes the key commerce fields for messaging and comments.'
                    : 'Webhook subscription is missing required fields: ' . implode(', ', $missingWebhookFields),
                [
                    'verified_fields' => $verifiedWebhookFields,
                    'missing_fields' => $missingWebhookFields,
                ]
            ),
            $this->makeReadinessCheck(
                'catalog_discovery',
                $localStats['meta_catalog_count'] > 0 ? 'ok' : 'fail',
                $localStats['meta_catalog_count'] > 0
                    ? 'Meta catalogs are discovered for this Instagram account.'
                    : 'No Meta catalogs are discovered for this Instagram account yet.',
                [
                    'meta_catalog_count' => $localStats['meta_catalog_count'],
                ]
            ),
            $this->makeReadinessCheck(
                'catalog_access',
                collect($liveCatalogs)->every(fn (array $catalog) => $catalog['live_ok']) ? 'ok' : 'warn',
                collect($liveCatalogs)->isEmpty()
                    ? 'No live Meta catalog details available yet.'
                    : (collect($liveCatalogs)->every(fn (array $catalog) => $catalog['live_ok'])
                        ? 'Live Meta catalog details are readable for all discovered catalogs.'
                        : 'Some discovered Meta catalogs could not be queried live.'),
                [
                    'catalogs' => $liveCatalogs,
                ]
            ),
            $this->makeReadinessCheck(
                'checkout_urls',
                ($checkoutSummary['checked_count'] ?? 0) === 0
                    ? 'warn'
                    : ((($checkoutSummary['invalid_count'] ?? 0) === 0) ? 'ok' : 'warn'),
                ($checkoutSummary['checked_count'] ?? 0) === 0
                    ? 'Add checkout URLs to products, localized profiles, or offers so review flows can be demonstrated end to end.'
                    : ((($checkoutSummary['invalid_count'] ?? 0) === 0)
                    ? 'Checkout URLs are valid HTTPS links across products, localized profiles, and offers.'
                    : 'Some checkout URLs are missing HTTPS or are not valid absolute URLs.'),
                $checkoutSummary
            ),
            $this->makeReadinessCheck(
                'shop_structure',
                $localStats['product_set_count'] > 0 && $localStats['collection_count'] > 0 ? 'ok' : 'warn',
                $localStats['product_set_count'] > 0 && $localStats['collection_count'] > 0
                    ? 'Shop structure is present with product sets and collections.'
                    : 'Create product sets and collections to complete the shop structure proof.',
                [
                    'product_set_count' => $localStats['product_set_count'],
                    'collection_count' => $localStats['collection_count'],
                ]
            ),
            $this->makeReadinessCheck(
                'orders_foundation',
                $localStats['order_count'] > 0 && $localStats['order_snapshot_count'] > 0 ? 'ok' : 'warn',
                $localStats['order_count'] > 0 && $localStats['order_snapshot_count'] > 0
                    ? 'Orders and snapshots are present for review and demo flows.'
                    : 'Create at least one test order and capture a snapshot to prove the order flow.',
                [
                    'order_count' => $localStats['order_count'],
                    'test_order_count' => $localStats['test_order_count'],
                    'order_snapshot_count' => $localStats['order_snapshot_count'],
                ]
            ),
            $this->makeReadinessCheck(
                'promotion_foundation',
                $localStats['promotion_campaign_count'] > 0 && $localStats['prepared_promotion_campaign_count'] > 0 ? 'ok' : 'warn',
                $localStats['promotion_campaign_count'] > 0 && $localStats['prepared_promotion_campaign_count'] > 0
                    ? 'Promotion campaigns and prepared ad previews are available for review.'
                    : 'Create at least one promotion campaign and prepare it to prove collection ads or promoted post flows.',
                [
                    'promotion_campaign_count' => $localStats['promotion_campaign_count'],
                    'prepared_promotion_campaign_count' => $localStats['prepared_promotion_campaign_count'],
                    'promoted_post_campaign_count' => $localStats['promoted_post_campaign_count'],
                    'collection_ad_campaign_count' => $localStats['collection_ad_campaign_count'],
                    'shops_ad_campaign_count' => $localStats['shops_ad_campaign_count'],
                ]
            ),
        ];

        $review = $this->buildReviewSummary(
            $connection,
            $accountBody,
            $requiredPermissions,
            $missingPermissions,
            $verifiedWebhookFields,
            $liveCatalogs,
            $localStats,
            $checkoutSummary,
            $readiness
        );

        return [
            'ok' => collect($readiness)->every(fn (array $item) => ($item['status'] ?? null) !== 'fail'),
            'checked_at' => now()->toIso8601String(),
            'graph_version' => $graphVersion,
            'provider_connection_id' => $connection->id,
            'provider_account_id' => $accountId,
            'account' => [
                'id' => $accountBody['id'] ?? null,
                'username' => $accountBody['username'] ?? null,
                'name' => $accountBody['name'] ?? null,
                'ig_id' => $accountBody['ig_id'] ?? null,
                'shopping_product_tag_eligibility' => $accountBody['shopping_product_tag_eligibility'] ?? null,
                'shopping_review_status' => $accountBody['shopping_review_status'] ?? null,
            ],
            'permissions' => [
                'required' => $requiredPermissions,
                'granted' => $grantedPermissions,
                'missing' => $missingPermissions,
                'local_permissions' => $connection->permissions()
                    ->orderBy('permission')
                    ->get(['permission', 'status'])
                    ->map(fn ($permission) => [
                        'permission' => $permission->permission,
                        'status' => $permission->status,
                    ])
                    ->values()
                    ->all(),
            ],
            'channel' => [
                'status' => $connection->status,
                'connected_at' => optional($connection->connected_at)?->toIso8601String(),
                'last_synced_at' => optional($connection->last_synced_at)?->toIso8601String(),
                'token_expires_at' => optional($tokenExpiresAt)?->toIso8601String(),
                'token_expired' => $tokenExpired,
                'provider_account_type' => $connection->provider_account_type,
                'live_subscribed_fields' => $verifiedWebhookFields,
                'live_subscribed_apps' => collect(Arr::get($checks['subscribed_apps'], 'body.data', []))
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn ($item) => [
                        'id' => $item['id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'subscribed_fields' => array_values(array_filter((array) ($item['subscribed_fields'] ?? []))),
                    ])
                    ->values()
                    ->all(),
            ],
            'webhook' => [
                'success' => (bool) ($webhookMeta['success'] ?? false),
                'verified_fields' => $verifiedWebhookFields,
                'missing_fields' => $missingWebhookFields,
                'verified_at' => $webhookMeta['verified_at'] ?? null,
                'error' => $webhookMeta['error'] ?? null,
            ],
            'shop' => [
                'meta_catalogs' => $liveCatalogs,
                'local_stats' => $localStats,
            ],
            'checkout_urls' => $checkoutSummary,
            'review' => $review,
            'readiness' => $readiness,
            'checks' => $checks,
        ];
    }

    protected function buildReviewSummary(
        ProviderConnection $connection,
        array $accountBody,
        array $requiredPermissions,
        array $missingPermissions,
        array $verifiedWebhookFields,
        array $liveCatalogs,
        array $localStats,
        array $checkoutSummary,
        array $readiness
    ): array {
        $counts = [
            'ok' => collect($readiness)->where('status', 'ok')->count(),
            'warn' => collect($readiness)->where('status', 'warn')->count(),
            'fail' => collect($readiness)->where('status', 'fail')->count(),
            'total' => count($readiness),
        ];

        $status = match (true) {
            $counts['fail'] > 0 => 'blocked',
            $counts['warn'] > 0 => 'needs_attention',
            default => 'ready',
        };

        $headline = match ($status) {
            'blocked' => 'App Review proof is blocked until the failing readiness checks are fixed.',
            'needs_attention' => 'Core commerce connectivity works, but a few proof items still need attention before review.',
            default => 'This Instagram commerce connection is ready to demonstrate for App Review.',
        };

        $evidence = [
            $this->makeEvidenceItem(
                'instagram_account',
                'Instagram business account',
                filled($connection->provider_account_id) ? 'ok' : 'fail',
                filled($accountBody['username'] ?? null)
                    ? 'Connected as @' . $accountBody['username'] . '.'
                    : 'Connection exists but the account identity could not be read live.'
            ),
            $this->makeEvidenceItem(
                'review_permissions',
                'Review permissions',
                count($missingPermissions) === 0 ? 'ok' : 'fail',
                count($missingPermissions) === 0
                    ? 'All configured review permissions are granted.'
                    : 'Missing permissions: ' . implode(', ', $missingPermissions),
                [
                    'required' => $requiredPermissions,
                    'missing' => $missingPermissions,
                ]
            ),
            $this->makeEvidenceItem(
                'product_tag_eligibility',
                'Product tag eligibility',
                !empty($accountBody['shopping_product_tag_eligibility']) ? 'ok' : 'warn',
                !empty($accountBody['shopping_product_tag_eligibility'])
                    ? 'Instagram reports that product tagging is eligible.'
                    : 'Instagram has not confirmed product-tag eligibility yet.'
            ),
            $this->makeEvidenceItem(
                'webhook_fields',
                'Webhook coverage',
                in_array('messages', $verifiedWebhookFields, true) && in_array('comments', $verifiedWebhookFields, true) ? 'ok' : 'warn',
                $verifiedWebhookFields !== []
                    ? 'Live webhook fields: ' . implode(', ', $verifiedWebhookFields)
                    : 'No live webhook fields were returned yet.'
            ),
            $this->makeEvidenceItem(
                'meta_catalogs',
                'Discovered Meta catalogs',
                count($liveCatalogs) > 0 ? 'ok' : 'fail',
                count($liveCatalogs) > 0
                    ? 'Found ' . count($liveCatalogs) . ' Meta catalog(s) for this account.'
                    : 'No Meta catalogs are discovered for this account yet.'
            ),
            $this->makeEvidenceItem(
                'live_catalog_access',
                'Live catalog access',
                count($liveCatalogs) === 0
                    ? 'warn'
                    : (collect($liveCatalogs)->every(fn (array $catalog) => !empty($catalog['live_ok'])) ? 'ok' : 'warn'),
                count($liveCatalogs) === 0
                    ? 'Run discovery and sync to pull in live catalog assets.'
                    : (collect($liveCatalogs)->every(fn (array $catalog) => !empty($catalog['live_ok']))
                        ? 'Live reads succeeded for all discovered Meta catalogs.'
                        : 'Some discovered Meta catalogs could not be queried live.')
            ),
            $this->makeEvidenceItem(
                'merchandising_layers',
                'Merchandising layers',
                ($localStats['product_set_count'] ?? 0) > 0 && ($localStats['collection_count'] ?? 0) > 0 ? 'ok' : 'warn',
                'Product sets: ' . ($localStats['product_set_count'] ?? 0) . ', collections: ' . ($localStats['collection_count'] ?? 0) . '.'
            ),
            $this->makeEvidenceItem(
                'localized_merchandising',
                'Localized pricing and offers',
                ($localStats['market_override_count'] ?? 0) > 0 || ($localStats['offer_count'] ?? 0) > 0 ? 'ok' : 'warn',
                'Localized profiles: ' . ($localStats['market_override_count'] ?? 0) . ', offers: ' . ($localStats['offer_count'] ?? 0) . '.'
            ),
            $this->makeEvidenceItem(
                'checkout_flows',
                'Checkout flow links',
                ($checkoutSummary['checked_count'] ?? 0) === 0
                    ? 'warn'
                    : ((($checkoutSummary['invalid_count'] ?? 0) === 0) ? 'ok' : 'warn'),
                ($checkoutSummary['checked_count'] ?? 0) === 0
                    ? 'No checkout URLs are configured yet.'
                    : ('Checked ' . ($checkoutSummary['checked_count'] ?? 0) . ' URL(s), invalid ' . ($checkoutSummary['invalid_count'] ?? 0) . '.')
            ),
            $this->makeEvidenceItem(
                'orders_and_snapshots',
                'Orders and snapshots',
                ($localStats['order_count'] ?? 0) > 0 && ($localStats['order_snapshot_count'] ?? 0) > 0 ? 'ok' : 'warn',
                'Orders: ' . ($localStats['order_count'] ?? 0) . ', test orders: ' . ($localStats['test_order_count'] ?? 0) . ', snapshots: ' . ($localStats['order_snapshot_count'] ?? 0) . '.'
            ),
            $this->makeEvidenceItem(
                'ads_and_promotions',
                'Ads and promotion foundation',
                ($localStats['promotion_campaign_count'] ?? 0) > 0 && ($localStats['prepared_promotion_campaign_count'] ?? 0) > 0 ? 'ok' : 'warn',
                'Campaigns: ' . ($localStats['promotion_campaign_count'] ?? 0)
                    . ', prepared: ' . ($localStats['prepared_promotion_campaign_count'] ?? 0)
                    . ', promoted posts: ' . ($localStats['promoted_post_campaign_count'] ?? 0)
                    . ', collection ads: ' . ($localStats['collection_ad_campaign_count'] ?? 0)
                    . ', shops ads: ' . ($localStats['shops_ad_campaign_count'] ?? 0) . '.'
            ),
        ];

        return [
            'status' => $status,
            'headline' => $headline,
            'counts' => $counts,
            'blockers' => collect($readiness)
                ->where('status', 'fail')
                ->map(fn (array $item) => [
                    'key' => $item['key'] ?? null,
                    'summary' => $item['summary'] ?? null,
                ])
                ->values()
                ->all(),
            'warnings' => collect($readiness)
                ->where('status', 'warn')
                ->map(fn (array $item) => [
                    'key' => $item['key'] ?? null,
                    'summary' => $item['summary'] ?? null,
                ])
                ->values()
                ->all(),
            'next_actions' => collect($readiness)
                ->filter(fn (array $item) => in_array($item['status'] ?? 'warn', ['fail', 'warn'], true))
                ->map(fn (array $item) => $this->nextActionForReadiness($item))
                ->filter()
                ->values()
                ->all(),
            'evidence' => $evidence,
        ];
    }

    protected function checkoutSummary(Collection $products): array
    {
        $entries = collect();

        foreach ($products as $product) {
            if (! $product instanceof CatalogProduct) {
                continue;
            }

            $entries->push([
                'type' => 'product_url',
                'label' => $product->title,
                'url' => $product->product_url,
            ]);

            foreach ($product->marketOverrides as $marketOverride) {
                $entries->push([
                    'type' => 'market_override_checkout_url',
                    'label' => $product->title . ' / ' . $marketOverride->target_country . ' / ' . $marketOverride->content_language,
                    'url' => $marketOverride->checkout_url,
                ]);
            }

            foreach ($product->offers as $offer) {
                $entries->push([
                    'type' => 'offer_checkout_url',
                    'label' => $product->title . ' / ' . $offer->name,
                    'url' => $offer->checkout_url,
                ]);
            }
        }

        $checked = $entries
            ->filter(fn (array $entry) => filled($entry['url'] ?? null))
            ->map(function (array $entry): array {
                $url = trim((string) ($entry['url'] ?? ''));
                $isValid = filter_var($url, FILTER_VALIDATE_URL) !== false;
                $isHttps = str_starts_with(strtolower($url), 'https://');

                return array_merge($entry, [
                    'is_valid' => $isValid,
                    'is_https' => $isHttps,
                ]);
            })
            ->values();

        return [
            'checked_count' => $checked->count(),
            'valid_count' => $checked->where('is_valid', true)->where('is_https', true)->count(),
            'invalid_count' => $checked->filter(fn (array $entry) => ! $entry['is_valid'] || ! $entry['is_https'])->count(),
            'invalid_examples' => $checked
                ->filter(fn (array $entry) => ! $entry['is_valid'] || ! $entry['is_https'])
                ->take(5)
                ->map(fn (array $entry) => [
                    'type' => $entry['type'],
                    'label' => $entry['label'],
                    'url' => $entry['url'],
                ])
                ->values()
                ->all(),
        ];
    }

    protected function extractGrantedPermissions(array $check): array
    {
        if (! ($check['ok'] ?? false)) {
            return [];
        }

        return collect(Arr::get($check, 'body.data', []))
            ->filter(fn ($row) => is_array($row) && ($row['status'] ?? null) === 'granted' && filled($row['permission'] ?? null))
            ->pluck('permission')
            ->map(fn ($permission) => (string) $permission)
            ->unique()
            ->values()
            ->all();
    }

    protected function makeReadinessCheck(string $key, string $status, string $summary, array $details = []): array
    {
        return [
            'key' => $key,
            'status' => $status,
            'summary' => $summary,
            'details' => $details,
        ];
    }

    protected function makeEvidenceItem(string $key, string $label, string $status, string $summary, array $details = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'summary' => $summary,
            'details' => $details,
        ];
    }

    protected function nextActionForReadiness(array $item): ?array
    {
        return match ($item['key'] ?? null) {
            'channel_health' => [
                'key' => 'channel_health',
                'title' => 'Reconnect the Instagram channel',
                'summary' => 'Refresh the primary access token so live commerce and review checks can run without expiry issues.',
            ],
            'permissions' => [
                'key' => 'permissions',
                'title' => 'Complete the review permission set',
                'summary' => 'Grant every configured commerce review permission on the Meta app and reconnect the account.',
            ],
            'product_tag_eligibility' => [
                'key' => 'product_tag_eligibility',
                'title' => 'Confirm shop eligibility for product tagging',
                'summary' => 'Finish the account-side shop setup in Meta so Instagram reports product-tag eligibility.',
            ],
            'webhook_subscription' => [
                'key' => 'webhook_subscription',
                'title' => 'Verify required webhook fields',
                'summary' => 'Keep messages and comments subscribed so review demos can show live messaging and comment activity.',
            ],
            'catalog_discovery' => [
                'key' => 'catalog_discovery',
                'title' => 'Run catalog discovery for this account',
                'summary' => 'Pull the real Meta catalogs into Leadochat so the shop structure can be demonstrated.',
            ],
            'catalog_access' => [
                'key' => 'catalog_access',
                'title' => 'Fix live catalog access',
                'summary' => 'Check token scope and catalog ownership until every discovered catalog responds successfully.',
            ],
            'checkout_urls' => [
                'key' => 'checkout_urls',
                'title' => 'Add clean HTTPS checkout URLs',
                'summary' => 'Use absolute HTTPS links on products, localized profiles, or offers to prove the purchase handoff.',
            ],
            'shop_structure' => [
                'key' => 'shop_structure',
                'title' => 'Build the final shop structure',
                'summary' => 'Create product sets and collections so the catalog can be reviewed as a real storefront hierarchy.',
            ],
            'orders_foundation' => [
                'key' => 'orders_foundation',
                'title' => 'Create test orders and snapshots',
                'summary' => 'Record at least one test order and capture snapshots so order state changes can be demonstrated during review.',
            ],
            'promotion_foundation' => [
                'key' => 'promotion_foundation',
                'title' => 'Prepare collection ads and promoted posts',
                'summary' => 'Create at least one campaign and generate a prepared preview so ad review evidence exists for collection ads, shops ads, or promoted posts.',
            ],
            default => null,
        };
    }

    protected function requestJson(string $accessToken, string $url, array $query = []): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url, $query);

            $json = $response->json();

            return [
                'url' => $url,
                'query' => $query,
                'status' => $response->status(),
                'ok' => $response->successful(),
                'error' => $response->successful() ? null : Arr::get($json, 'error.message', $response->body()),
                'body' => is_array($json) ? $json : ['raw' => mb_substr($response->body(), 0, 1000)],
            ];
        } catch (\Throwable $exception) {
            return [
                'url' => $url,
                'query' => $query,
                'status' => null,
                'ok' => false,
                'error' => $exception->getMessage(),
                'body' => [],
            ];
        }
    }

    protected function resolveAccessToken(ProviderConnection $connection): string
    {
        $token = $this->primaryToken($connection);
        $accessToken = (string) ($token?->access_token ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('No primary Meta access token found for this connection.');
        }

        return $accessToken;
    }

    protected function resolveGraphVersion(): string
    {
        return (string) config('services.meta.graph_version', config('services.instagram.graph_version', 'v25.0'));
    }

    protected function requiredReviewScopes(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.meta.commerce_review_scopes', ''))
        ))));
    }

    protected function primaryToken(ProviderConnection $connection): ?OauthToken
    {
        return $connection->oauthTokens()
            ->where('token_type', 'access_token')
            ->where('is_primary', true)
            ->latest('id')
            ->first();
    }
}
