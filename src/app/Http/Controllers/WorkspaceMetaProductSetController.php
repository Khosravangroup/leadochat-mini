<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceMetaProductSetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $validated = $request->validate([
            'meta_catalog_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $metaCatalog = Catalog::query()
            ->where('workspace_id', $workspace->id)
            ->where('source', 'meta')
            ->whereKey((int) $validated['meta_catalog_id'])
            ->firstOrFail();

        CatalogProductSet::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $metaCatalog->provider_connection_id,
            'catalog_id' => $metaCatalog->id,
            'name' => trim((string) $validated['name']),
            'description' => filled($validated['description'] ?? null)
                ? trim((string) $validated['description'])
                : null,
            'status' => 'active',
            'meta_sync_status' => 'not_synced',
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Product set created.');
    }

    public function syncProducts(Request $request, CatalogProductSet $productSet): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceProductSet($productSet, $workspace?->id);

        $validated = $request->validate([
            'product_ids' => ['nullable', 'array', 'max:100'],
            'product_ids.*' => ['integer'],
        ]);

        $productIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $validated['product_ids'] ?? []
        ))));

        $allowedProducts = CatalogProduct::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->whereIn('meta_sync_status', ['queued', 'synced'])
            ->whereHas('catalog', function ($query) use ($workspace, $productSet) {
                $query->where('workspace_id', $workspace->id)
                    ->where('status', 'active')
                    ->where('source', '!=', 'meta')
                    ->where(function ($catalogQuery) use ($productSet) {
                        $catalogQuery->whereNull('provider_connection_id')
                            ->orWhere('provider_connection_id', $productSet->provider_connection_id);
                    });
            })
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach (array_values($productIds) as $index => $productId) {
            if (! in_array($productId, $allowedProducts, true)) {
                continue;
            }

            $syncPayload[$productId] = [
                'sort_order' => $index,
            ];
        }

        $productSet->products()->sync($syncPayload);

        $meta = is_array($productSet->meta) ? $productSet->meta : [];
        $meta['last_product_assignment'] = [
            'product_count' => count($syncPayload),
            'updated_at' => now()->toIso8601String(),
        ];

        $productSet->update([
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', count($syncPayload) . ' product(s) assigned to the product set.');
    }

    public function destroy(Request $request, CatalogProductSet $productSet): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceProductSet($productSet, $workspace?->id);

        $productSet->delete();

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Product set removed.');
    }

    protected function guardWorkspaceProductSet(?CatalogProductSet $productSet, ?int $workspaceId): void
    {
        if (! $workspaceId || ! $productSet || $productSet->workspace_id !== $workspaceId) {
            abort(404);
        }
    }
}
