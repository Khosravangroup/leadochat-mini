<?php

namespace App\Services\Meta\Commerce;

use App\Models\CommercePromotionCampaign;
use Illuminate\Support\Arr;

class MetaCommercePromotionPreviewService
{
    public function prepare(CommercePromotionCampaign $campaign): array
    {
        $campaign->loadMissing([
            'providerConnection',
            'collection.productSets.products',
            'socialPost.providerConnection',
        ]);

        $collection = $campaign->collection;
        $socialPost = $campaign->socialPost;
        $productTags = is_array($socialPost?->raw) ? (array) ($socialPost->raw['product_tags'] ?? []) : [];
        $collectionProductSetCount = $collection?->productSets->count() ?? 0;
        $collectionProductCount = $collection
            ? $collection->productSets->sum(fn ($set) => $set->products->count())
            : 0;

        $checks = [
            $this->makeCheck(
                'budget',
                (float) $campaign->budget_amount > 0 ? 'ok' : 'warn',
                (float) $campaign->budget_amount > 0
                    ? 'Campaign budget is defined.'
                    : 'Set a campaign budget before promoting this asset.'
            ),
            $this->makeCheck(
                'destination_url',
                $this->isValidHttpsUrl($campaign->destination_url) ? 'ok' : 'warn',
                $this->isValidHttpsUrl($campaign->destination_url)
                    ? 'Destination URL is a valid HTTPS link.'
                    : 'Destination URL should be a valid HTTPS link for campaign handoff.'
            ),
            $this->makeCheck(
                'channel_alignment',
                $this->assetBelongsToConnection($campaign) ? 'ok' : 'fail',
                $this->assetBelongsToConnection($campaign)
                    ? 'Campaign assets belong to the selected Instagram connection.'
                    : 'Selected assets do not belong to the same Instagram connection.'
            ),
        ];

        if (in_array($campaign->campaign_type, ['collection_ad', 'shops_ad'], true)) {
            $checks[] = $this->makeCheck(
                'collection_asset',
                $collection && $collectionProductSetCount > 0 ? 'ok' : 'warn',
                $collection && $collectionProductSetCount > 0
                    ? 'Collection is linked and contains product sets.'
                    : 'Choose a collection with at least one assigned product set.'
            );
        }

        if ($campaign->campaign_type === 'promoted_post') {
            $checks[] = $this->makeCheck(
                'post_asset',
                $socialPost && filled($socialPost->provider_media_id) ? 'ok' : 'warn',
                $socialPost && filled($socialPost->provider_media_id)
                    ? 'Instagram post is linked and published.'
                    : 'Choose a published Instagram post before preparing a promoted post.'
            );

            $checks[] = $this->makeCheck(
                'post_product_tags',
                count($productTags) > 0 ? 'ok' : 'warn',
                count($productTags) > 0
                    ? 'Linked Instagram post includes product tags.'
                    : 'Promoted post is not product-tagged yet. Tagged posts are stronger review evidence for commerce ads.'
            );
        }

        $issues = collect($checks)
            ->whereIn('status', ['warn', 'fail'])
            ->pluck('summary')
            ->values()
            ->all();

        return [
            'ok' => collect($checks)->every(fn (array $check) => ($check['status'] ?? null) !== 'fail'),
            'prepared_at' => now()->toIso8601String(),
            'campaign_type' => $campaign->campaign_type,
            'checks' => $checks,
            'issues' => $issues,
            'asset_summary' => [
                'collection_name' => $collection?->name,
                'collection_product_set_count' => $collectionProductSetCount,
                'collection_product_count' => $collectionProductCount,
                'social_post_id' => $socialPost?->id,
                'social_post_media_id' => $socialPost?->provider_media_id,
                'social_post_permalink' => $socialPost?->permalink,
                'social_post_product_tag_count' => count($productTags),
            ],
            'payload_preview' => [
                'campaign' => [
                    'name' => $campaign->name,
                    'campaign_type' => $campaign->campaign_type,
                    'objective' => $campaign->objective,
                    'status' => $campaign->status,
                    'budget_amount' => (float) $campaign->budget_amount,
                    'currency' => $campaign->currency,
                    'call_to_action' => $campaign->call_to_action,
                    'destination_url' => $campaign->destination_url,
                    'starts_at' => optional($campaign->starts_at)->toIso8601String(),
                    'ends_at' => optional($campaign->ends_at)->toIso8601String(),
                ],
                'connection' => [
                    'provider_account_id' => $campaign->providerConnection?->provider_account_id,
                    'provider_account_name' => $campaign->providerConnection?->provider_account_name,
                ],
                'collection' => $collection ? [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'external_collection_id' => $collection->external_collection_id,
                    'product_set_count' => $collectionProductSetCount,
                    'product_count' => $collectionProductCount,
                ] : null,
                'social_post' => $socialPost ? [
                    'id' => $socialPost->id,
                    'provider_media_id' => $socialPost->provider_media_id,
                    'permalink' => $socialPost->permalink,
                    'product_tags' => $productTags,
                    'product_tag_count' => count($productTags),
                ] : null,
            ],
        ];
    }

    protected function makeCheck(string $key, string $status, string $summary): array
    {
        return [
            'key' => $key,
            'status' => $status,
            'summary' => $summary,
        ];
    }

    protected function isValidHttpsUrl(?string $url): bool
    {
        $url = trim((string) $url);

        return $url !== ''
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && str_starts_with(strtolower($url), 'https://');
    }

    protected function assetBelongsToConnection(CommercePromotionCampaign $campaign): bool
    {
        $connectionId = (int) $campaign->provider_connection_id;

        if ($campaign->catalog_collection_id && (int) ($campaign->collection?->provider_connection_id ?? 0) !== $connectionId) {
            return false;
        }

        if ($campaign->social_post_id && (int) ($campaign->socialPost?->provider_connection_id ?? 0) !== $connectionId) {
            return false;
        }

        return true;
    }
}
