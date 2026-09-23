<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\ProviderConnection;
use App\Services\Meta\Commerce\MetaAppReviewEvidenceService;
use App\Services\Meta\Commerce\MetaCatalogProductSyncService;
use App\Services\Meta\Commerce\MetaCommerceDiagnosticsService;
use App\Services\Meta\Commerce\MetaCommerceDiscoveryService;
use App\Services\Meta\Commerce\MetaCommerceReviewPacketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceMetaCommerceController extends Controller
{
    public function sync(
        Request $request,
        ProviderConnection $connection,
        MetaCommerceDiscoveryService $discoveryService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $discovery = $discoveryService->discoverForConnection($connection);

        DB::transaction(function () use ($connection, $workspace, $discovery) {
            foreach ($discovery['catalogs'] ?? [] as $catalog) {
                Catalog::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'provider_connection_id' => $connection->id,
                        'external_catalog_id' => (string) ($catalog['id'] ?? ''),
                    ],
                    [
                        'source' => 'meta',
                        'external_business_id' => $catalog['business_id'] ?? null,
                        'name' => $catalog['name'] ?? 'Meta Catalog',
                        'status' => 'active',
                        'meta_sync_status' => 'discovered',
                        'meta_sync_error' => null,
                        'last_synced_at' => now(),
                        'meta_synced_at' => now(),
                        'meta' => [
                            'meta_catalog' => $catalog,
                            'discovered_at' => now()->toIso8601String(),
                        ],
                    ]
                );
            }

            $meta = is_array($connection->meta) ? $connection->meta : [];
            $meta['meta_commerce_discovery'] = $discovery;
            $meta['meta_commerce'] = array_merge($meta['meta_commerce'] ?? [], [
                'last_discovered_at' => now()->toIso8601String(),
                'business_ids' => $discovery['business_ids'] ?? [],
                'catalog_count' => count($discovery['catalogs'] ?? []),
                'review_scopes' => $discovery['review_scopes'] ?? [],
            ]);

            $connection->update([
                'last_synced_at' => now(),
                'meta' => $meta,
            ]);
        });

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Meta Commerce discovery finished. Found '.count($discovery['catalogs'] ?? []).' catalog(s).');
    }

    public function syncProducts(
        Request $request,
        Catalog $catalog,
        MetaCatalogProductSyncService $syncService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $catalog->workspace_id === $workspace->id
            && $catalog->source === 'meta',
            404
        );

        $validated = $request->validate([
            'source_catalog_id' => ['required', 'integer'],
        ]);

        $sourceCatalog = Catalog::query()
            ->where('workspace_id', $workspace->id)
            ->whereKey((int) $validated['source_catalog_id'])
            ->with('products')
            ->firstOrFail();

        $result = $syncService->syncProducts($sourceCatalog, $catalog);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with(
                'status',
                $result['ok']
                    ? "Meta product sync queued for {$result['product_count']} product(s)."
                    : "Meta product sync failed for {$result['product_count']} product(s)."
            );
    }

    public function diagnostics(
        Request $request,
        ProviderConnection $connection,
        MetaCommerceDiagnosticsService $diagnosticsService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $diagnostics = $diagnosticsService->diagnoseForConnection($connection);

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $meta['meta_commerce_diagnostics'] = $diagnostics;
        $meta['meta_commerce'] = array_merge($meta['meta_commerce'] ?? [], [
            'last_diagnostics_at' => now()->toIso8601String(),
        ]);

        $connection->update([
            'last_synced_at' => now(),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Meta Commerce diagnostics finished.');
    }

    public function generateReviewPacket(
        Request $request,
        ProviderConnection $connection,
        MetaCommerceDiagnosticsService $diagnosticsService,
        MetaCommerceReviewPacketService $reviewPacketService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $diagnostics = $diagnosticsService->diagnoseForConnection($connection);
        $packet = $reviewPacketService->generateForConnection($connection, $diagnostics);

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $history = collect((array) ($meta['meta_commerce_review_packet_history'] ?? []))
            ->filter(fn ($entry) => is_array($entry))
            ->prepend([
                'generated_at' => $packet['generated_at'] ?? now()->toIso8601String(),
                'status' => data_get($packet, 'summary.status'),
                'headline' => data_get($packet, 'summary.headline'),
                'discovered_catalog_count' => data_get($packet, 'catalogs.discovered_count', 0),
                'order_count' => data_get($packet, 'orders.order_count', 0),
                'snapshot_count' => data_get($packet, 'orders.snapshot_count', 0),
                'campaign_count' => data_get($packet, 'promotions.campaign_count', 0),
                'prepared_campaign_count' => data_get($packet, 'promotions.prepared_campaign_count', 0),
                'blocker_count' => count((array) data_get($packet, 'review_evidence.blockers', [])),
                'warning_count' => count((array) data_get($packet, 'review_evidence.warnings', [])),
            ])
            ->take(5)
            ->values()
            ->all();

        $meta['meta_commerce_diagnostics'] = $diagnostics;
        $meta['meta_commerce_review_packet'] = $packet;
        $meta['meta_commerce_review_packet_history'] = $history;
        $meta['meta_commerce'] = array_merge($meta['meta_commerce'] ?? [], [
            'last_diagnostics_at' => now()->toIso8601String(),
            'last_review_packet_at' => $packet['generated_at'] ?? now()->toIso8601String(),
        ]);

        $connection->update([
            'last_synced_at' => now(),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Meta Commerce review packet generated.');
    }

    public function downloadReviewPacket(
        Request $request,
        ProviderConnection $connection,
        MetaCommerceReviewPacketService $reviewPacketService
    ): StreamedResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $packet = is_array($meta['meta_commerce_review_packet'] ?? null)
            ? $meta['meta_commerce_review_packet']
            : null;

        abort_unless($packet !== null, 404);

        $filename = $reviewPacketService->exportFilename($connection, $packet);
        $json = json_encode($packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($json): void {
            echo $json ?: '{}';
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function generateAppReviewEvidence(
        Request $request,
        ProviderConnection $connection,
        MetaCommerceDiagnosticsService $diagnosticsService,
        MetaCommerceReviewPacketService $reviewPacketService,
        MetaAppReviewEvidenceService $appReviewEvidenceService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $commerceScopes = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.meta.commerce_review_scopes', ''))
        )));
        $isCommerceReviewEnabled = $commerceScopes !== [];

        if ($isCommerceReviewEnabled) {
            $diagnostics = $diagnosticsService->diagnoseForConnection($connection);
            $reviewPacket = $reviewPacketService->generateForConnection($connection, $diagnostics);
        } else {
            $diagnostics = [
                'account' => [
                    'username' => $connection->provider_account_name,
                    'name' => $connection->provider_account_name,
                ],
                'permissions' => $diagnosticsService->instagramReviewConfiguration(),
            ];
            $reviewPacket = [];
        }
        $appReviewEvidence = $appReviewEvidenceService->generateForConnection($connection, $diagnostics);

        $meta = is_array($connection->meta) ? $connection->meta : [];

        $appReviewHistory = collect((array) ($meta['meta_app_review_evidence_history'] ?? []))
            ->filter(fn ($entry) => is_array($entry))
            ->prepend([
                'generated_at' => $appReviewEvidence['generated_at'] ?? now()->toIso8601String(),
                'status' => data_get($appReviewEvidence, 'summary.status'),
                'headline' => data_get($appReviewEvidence, 'summary.headline'),
                'conversation_count' => data_get($appReviewEvidence, 'coverage.inbox.conversation_count', 0),
                'message_count' => data_get($appReviewEvidence, 'coverage.inbox.message_count', 0),
                'comment_count' => data_get($appReviewEvidence, 'coverage.social.comment_count', 0),
                'story_count' => data_get($appReviewEvidence, 'coverage.social.story_count', 0),
                'webhook_event_count' => data_get($appReviewEvidence, 'coverage.webhooks.event_count', 0),
                'blocker_count' => count((array) data_get($appReviewEvidence, 'evidence.blockers', [])),
                'warning_count' => count((array) data_get($appReviewEvidence, 'evidence.warnings', [])),
            ])
            ->take(5)
            ->values()
            ->all();

        if ($isCommerceReviewEnabled) {
            $reviewPacketHistory = collect((array) ($meta['meta_commerce_review_packet_history'] ?? []))
                ->filter(fn ($entry) => is_array($entry))
                ->prepend([
                    'generated_at' => $reviewPacket['generated_at'] ?? now()->toIso8601String(),
                    'status' => data_get($reviewPacket, 'summary.status'),
                    'headline' => data_get($reviewPacket, 'summary.headline'),
                    'discovered_catalog_count' => data_get($reviewPacket, 'catalogs.discovered_count', 0),
                    'order_count' => data_get($reviewPacket, 'orders.order_count', 0),
                    'snapshot_count' => data_get($reviewPacket, 'orders.snapshot_count', 0),
                    'campaign_count' => data_get($reviewPacket, 'promotions.campaign_count', 0),
                    'prepared_campaign_count' => data_get($reviewPacket, 'promotions.prepared_campaign_count', 0),
                    'blocker_count' => count((array) data_get($reviewPacket, 'review_evidence.blockers', [])),
                    'warning_count' => count((array) data_get($reviewPacket, 'review_evidence.warnings', [])),
                ])
                ->take(5)
                ->values()
                ->all();

            $meta['meta_commerce_diagnostics'] = $diagnostics;
            $meta['meta_commerce_review_packet'] = $reviewPacket;
            $meta['meta_commerce_review_packet_history'] = $reviewPacketHistory;
        }
        $meta['meta_app_review_evidence'] = $appReviewEvidence;
        $meta['meta_app_review_evidence_history'] = $appReviewHistory;
        $meta['meta_commerce'] = array_merge($meta['meta_commerce'] ?? [], [
            'last_app_review_evidence_at' => $appReviewEvidence['generated_at'] ?? now()->toIso8601String(),
        ]);

        if ($isCommerceReviewEnabled) {
            $meta['meta_commerce'] = array_merge($meta['meta_commerce'], [
                'last_diagnostics_at' => now()->toIso8601String(),
                'last_review_packet_at' => $reviewPacket['generated_at'] ?? now()->toIso8601String(),
            ]);
        }

        $connection->update([
            'last_synced_at' => now(),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Meta App Review evidence packet generated.');
    }

    public function downloadAppReviewEvidence(
        Request $request,
        ProviderConnection $connection,
        MetaAppReviewEvidenceService $appReviewEvidenceService
    ): StreamedResponse {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless(
            $workspace
            && $connection->workspace_id === $workspace->id
            && $connection->provider === 'instagram',
            404
        );

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $packet = is_array($meta['meta_app_review_evidence'] ?? null)
            ? $meta['meta_app_review_evidence']
            : null;

        abort_unless($packet !== null, 404);

        $filename = $appReviewEvidenceService->exportFilename($connection, $packet);
        $json = json_encode($packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($json): void {
            echo $json ?: '{}';
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
