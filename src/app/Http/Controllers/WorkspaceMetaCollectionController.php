<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProductSet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkspaceMetaCollectionController extends Controller
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

        CatalogCollection::create([
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
            ->with('status', 'Collection created.');
    }

    public function syncProductSets(Request $request, CatalogCollection $collection): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCollection($collection, $workspace?->id);

        $validated = $request->validate([
            'product_set_ids' => ['nullable', 'array', 'max:100'],
            'product_set_ids.*' => ['integer'],
        ]);

        $productSetIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $validated['product_set_ids'] ?? []
        ))));

        $allowedProductSets = CatalogProductSet::query()
            ->whereIn('id', $productSetIds)
            ->where('status', 'active')
            ->where('catalog_id', $collection->catalog_id)
            ->where('workspace_id', $workspace->id)
            ->pluck('id')
            ->all();

        $syncPayload = [];

        foreach (array_values($productSetIds) as $index => $productSetId) {
            if (! in_array($productSetId, $allowedProductSets, true)) {
                continue;
            }

            $syncPayload[$productSetId] = [
                'sort_order' => $index,
            ];
        }

        $collection->productSets()->sync($syncPayload);

        $meta = is_array($collection->meta) ? $collection->meta : [];
        $meta['last_product_set_assignment'] = [
            'product_set_count' => count($syncPayload),
            'updated_at' => now()->toIso8601String(),
        ];

        $collection->update([
            'meta' => $meta,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', count($syncPayload) . ' product set(s) assigned to the collection.');
    }

    public function destroy(Request $request, CatalogCollection $collection): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCollection($collection, $workspace?->id);

        $collection->delete();

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Collection removed.');
    }

    protected function guardWorkspaceCollection(?CatalogCollection $collection, ?int $workspaceId): void
    {
        if (! $workspaceId || ! $collection || $collection->workspace_id !== $workspaceId) {
            abort(404);
        }
    }
}
