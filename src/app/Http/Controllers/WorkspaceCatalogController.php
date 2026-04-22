<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogProduct;
use App\Models\ProviderConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

        $validated = $request->validate($this->productRules());

        $catalog->products()->create($this->normalizeProductAttributes($validated));

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', 'Product added to catalog.');
    }

    public function updateProduct(Request $request, CatalogProduct $product): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $catalog = $product->catalog;

        $this->guardWorkspaceCatalog($catalog, $workspace?->id);

        $validated = $request->validate($this->productRules([
            'is_active' => ['nullable', 'boolean'],
        ]));

        $product->update($this->normalizeProductAttributes($validated, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', 'Product updated.');
    }

    public function toggleProductStatus(Request $request, CatalogProduct $product): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $catalog = $product->catalog;

        $this->guardWorkspaceCatalog($catalog, $workspace?->id);

        $isActive = ! $product->is_active;

        $product->update([
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', $isActive ? 'Product enabled.' : 'Product disabled.');
    }

    public function importProducts(Request $request, Catalog $catalog): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceCatalog($catalog, $workspace?->id);

        $validated = $request->validate([
            'products_csv' => ['required', 'file', 'max:5120'],
        ]);

        $path = $validated['products_csv']->getRealPath();
        $handle = $path ? fopen($path, 'rb') : false;

        if (! $handle) {
            return redirect()
                ->route('settings.index', ['section' => 'catalogs'])
                ->withErrors(['products_csv' => 'Could not read the uploaded CSV file.']);
        }

        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rowNumber = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($rowNumber > 501) {
                    break;
                }

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->normalizeCsvHeader($row);
                    continue;
                }

                $attributes = $this->mapCsvProductRow($header, $row);

                if (! filled($attributes['title'] ?? null)) {
                    $skipped++;
                    continue;
                }

                $normalized = $this->normalizeProductAttributes($attributes, [
                    'is_active' => $this->parseBoolean($attributes['is_active'] ?? true),
                    'metadata' => [
                        'imported_at' => now()->toIso8601String(),
                        'imported_from' => 'settings_csv',
                    ],
                ]);

                $sku = (string) ($normalized['sku'] ?? '');
                $existing = $sku !== ''
                    ? $catalog->products()->where('sku', $sku)->first()
                    : null;

                if ($existing) {
                    $existing->update($normalized);
                    $updated++;
                } else {
                    $catalog->products()->create($normalized);
                    $created++;
                }
            }
        } finally {
            fclose($handle);
        }

        return redirect()
            ->route('settings.index', ['section' => 'catalogs'])
            ->with('status', "Catalog import finished. Created {$created}, updated {$updated}, skipped {$skipped}.");
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

    protected function productRules(array $extra = []): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1200'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'availability' => ['required', 'string', Rule::in(['in_stock', 'out_of_stock', 'preorder'])],
        ], $extra);
    }

    protected function normalizeProductAttributes(array $validated, array $extra = []): array
    {
        $currency = strtoupper(trim((string) ($validated['currency'] ?? 'USD')));

        if (strlen($currency) !== 3) {
            $currency = 'USD';
        }

        return array_filter(array_merge([
            'title' => mb_substr(trim((string) $validated['title']), 0, 180),
            'sku' => filled($validated['sku'] ?? null) ? mb_substr(trim((string) $validated['sku']), 0, 120) : null,
            'description' => filled($validated['description'] ?? null) ? mb_substr(trim((string) $validated['description']), 0, 1200) : null,
            'price' => filled($validated['price'] ?? null) ? (float) $validated['price'] : null,
            'currency' => $currency,
            'image_url' => filled($validated['image_url'] ?? null) ? mb_substr(trim((string) $validated['image_url']), 0, 2048) : null,
            'product_url' => filled($validated['product_url'] ?? null) ? mb_substr(trim((string) $validated['product_url']), 0, 2048) : null,
            'availability' => $validated['availability'] ?? 'in_stock',
            'is_active' => $extra['is_active'] ?? true,
            'metadata' => $extra['metadata'] ?? null,
        ], Arr::except($extra, ['is_active', 'metadata'])), fn ($value) => $value !== null);
    }

    protected function normalizeCsvHeader(array $row): array
    {
        return array_map(function ($value): string {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value) ?: '';
            $value = strtolower(trim($value));

            return match ($value) {
                'name', 'product_name' => 'title',
                'url', 'link', 'product_link' => 'product_url',
                'image', 'photo', 'image_link' => 'image_url',
                'status' => 'availability',
                'active', 'enabled' => 'is_active',
                default => $value,
            };
        }, $row);
    }

    protected function mapCsvProductRow(array $header, array $row): array
    {
        $values = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $values[$key] = trim((string) ($row[$index] ?? ''));
        }

        return [
            'title' => $values['title'] ?? '',
            'sku' => $values['sku'] ?? null,
            'description' => $values['description'] ?? null,
            'price' => $values['price'] ?? null,
            'currency' => $values['currency'] ?? 'USD',
            'image_url' => $values['image_url'] ?? null,
            'product_url' => $values['product_url'] ?? null,
            'availability' => $this->normalizeAvailability($values['availability'] ?? 'in_stock'),
            'is_active' => $values['is_active'] ?? true,
        ];
    }

    protected function normalizeAvailability(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'out', 'sold_out', 'out of stock', 'out-of-stock' => 'out_of_stock',
            'pre-order', 'pre order' => 'preorder',
            default => in_array($value, ['in_stock', 'out_of_stock', 'preorder'], true)
                ? $value
                : 'in_stock',
        };
    }

    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return ! in_array($value, ['0', 'false', 'no', 'off', 'inactive', 'disabled'], true);
    }

    protected function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
