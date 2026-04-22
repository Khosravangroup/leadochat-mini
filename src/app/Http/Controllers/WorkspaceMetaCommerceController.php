<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\ProviderConnection;
use App\Services\Meta\Commerce\MetaCatalogProductSyncService;
use App\Services\Meta\Commerce\MetaCommerceDiscoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->with('status', 'Meta Commerce discovery finished. Found ' . count($discovery['catalogs'] ?? []) . ' catalog(s).');
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
}
