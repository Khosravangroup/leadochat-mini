<?php

namespace App\Http\Controllers;

use App\Models\CatalogCollection;
use App\Models\CommercePromotionCampaign;
use App\Models\ProviderConnection;
use App\Models\SocialPost;
use App\Services\Meta\Commerce\MetaCommercePromotionPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceMetaPromotionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $validated = $request->validate([
            'provider_connection_id' => ['required', 'integer'],
            'campaign_type' => ['required', 'string', Rule::in($this->campaignTypes())],
            'objective' => ['required', 'string', Rule::in($this->objectives())],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in($this->statuses())],
            'call_to_action' => ['nullable', 'string', 'max:60'],
            'destination_url' => ['nullable', 'url', 'max:2048'],
            'catalog_collection_id' => ['nullable', 'integer'],
            'social_post_id' => ['nullable', 'integer'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $connection = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereKey((int) $validated['provider_connection_id'])
            ->firstOrFail();

        $collection = null;
        $socialPost = null;

        if (!empty($validated['catalog_collection_id'])) {
            $collection = CatalogCollection::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider_connection_id', $connection->id)
                ->whereKey((int) $validated['catalog_collection_id'])
                ->firstOrFail();
        }

        if (!empty($validated['social_post_id'])) {
            $socialPost = SocialPost::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider_connection_id', $connection->id)
                ->where('provider', 'instagram')
                ->where('status', '!=', 'deleted')
                ->whereKey((int) $validated['social_post_id'])
                ->firstOrFail();
        }

        CommercePromotionCampaign::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'catalog_collection_id' => $collection?->id,
            'social_post_id' => $socialPost?->id,
            'campaign_type' => $validated['campaign_type'],
            'objective' => $validated['objective'],
            'name' => trim((string) $validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'status' => $validated['status'],
            'call_to_action' => filled($validated['call_to_action'] ?? null) ? trim((string) $validated['call_to_action']) : null,
            'destination_url' => filled($validated['destination_url'] ?? null) ? trim((string) $validated['destination_url']) : null,
            'budget_amount' => round((float) $validated['budget_amount'], 2),
            'currency' => strtoupper((string) $validated['currency']),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'meta_sync_status' => 'not_prepared',
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Promotion campaign created.');
    }

    public function prepare(
        Request $request,
        CommercePromotionCampaign $campaign,
        MetaCommercePromotionPreviewService $previewService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCampaign($campaign, $workspace?->id);

        $prepared = $previewService->prepare($campaign);
        $meta = is_array($campaign->meta) ? $campaign->meta : [];
        $meta['last_prepared_preview'] = $prepared;
        $meta['last_prepared_at'] = $prepared['prepared_at'] ?? now()->toIso8601String();

        $issues = (array) ($prepared['issues'] ?? []);

        $campaign->update([
            'meta_sync_status' => !empty($prepared['ok']) && count($issues) === 0
                ? 'prepared'
                : (!empty($prepared['ok']) ? 'prepared_with_warnings' : 'needs_assets'),
            'meta_sync_error' => $issues !== [] ? implode(' | ', $issues) : null,
            'meta_synced_at' => now(),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Promotion campaign prepared for review.');
    }

    public function updateStatus(Request $request, CommercePromotionCampaign $campaign): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCampaign($campaign, $workspace?->id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($this->statuses())],
        ]);

        $campaign->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Promotion campaign status updated.');
    }

    public function destroy(Request $request, CommercePromotionCampaign $campaign): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCampaign($campaign, $workspace?->id);

        $campaign->delete();

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Promotion campaign removed.');
    }

    protected function guardWorkspaceCampaign(?CommercePromotionCampaign $campaign, ?int $workspaceId): void
    {
        if (! $workspaceId || ! $campaign || $campaign->workspace_id !== $workspaceId) {
            abort(404);
        }
    }

    protected function campaignTypes(): array
    {
        return ['collection_ad', 'shops_ad', 'promoted_post'];
    }

    protected function objectives(): array
    {
        return ['sales', 'traffic', 'engagement', 'catalog_sales'];
    }

    protected function statuses(): array
    {
        return ['draft', 'active', 'paused', 'archived'];
    }
}
