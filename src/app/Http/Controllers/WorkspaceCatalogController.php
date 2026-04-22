<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\ProviderConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceCatalogController extends Controller
{
    public function storeCatalog(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        if (! $workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'provider_connection_id' => ['nullable', 'integer'],
        ]);

        $providerConnectionId = $this->resolveProviderConnectionId(
            $workspace->id,
            isset($validated['provider_connection_id']) ? (int) $validated['provider_connection_id'] : null
        );

        Catalog::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $providerConnectionId,
            'source' => 'leadochat',
            'name' => trim($validated['name']),
            'status' => 'active',
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', 'Catalog created.');
    }

    public function storeProduct(Request $request, Catalog $catalog): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCatalog($catalog, $workspace?->id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1200'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'availability' => ['required', 'string', Rule::in(['in_stock', 'out_of_stock', 'preorder'])],
        ]);

        $catalog->products()->create([
            'title' => trim($validated['title']),
            'sku' => filled($validated['sku'] ?? null) ? trim($validated['sku']) : null,
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'price' => $validated['price'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'image_url' => filled($validated['image_url'] ?? null) ? trim($validated['image_url']) : null,
            'product_url' => filled($validated['product_url'] ?? null) ? trim($validated['product_url']) : null,
            'availability' => $validated['availability'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', 'Product added to catalog.');
    }

    public function deleteProduct(Request $request, CatalogProduct $product): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $catalog = $product->catalog;

        $this->guardWorkspaceCatalog($catalog, $workspace?->id);

        $product->delete();

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', 'Product removed from catalog.');
    }

    protected function guardWorkspaceCatalog(?Catalog $catalog, ?int $workspaceId): void
    {
        if (! $workspaceId || ! $catalog || $catalog->workspace_id !== $workspaceId) {
            abort(404);
        }
    }

    protected function resolveProviderConnectionId(int $workspaceId, ?int $providerConnectionId): ?int
    {
        if (! $providerConnectionId) {
            return null;
        }

        return ProviderConnection::query()
            ->where('workspace_id', $workspaceId)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereKey($providerConnectionId)
            ->value('id');
    }
}
