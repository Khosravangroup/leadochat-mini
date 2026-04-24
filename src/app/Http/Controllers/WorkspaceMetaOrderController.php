<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CommerceOrder;
use App\Models\ProviderConnection;
use App\Services\Meta\Commerce\MetaCommerceOrderSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkspaceMetaOrderController extends Controller
{
    public function store(Request $request, MetaCommerceOrderSnapshotService $snapshotService): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $validated = $request->validate([
            'provider_connection_id' => ['required', 'integer'],
            'catalog_id' => ['required', 'integer'],
            'catalog_collection_id' => ['nullable', 'integer'],
            'product_ids' => ['required', 'array', 'min:1', 'max:25'],
            'product_ids.*' => ['integer'],
            'external_order_id' => ['nullable', 'string', 'max:120'],
            'external_checkout_id' => ['nullable', 'string', 'max:120'],
            'customer_reference' => ['nullable', 'string', 'max:120'],
            'customer_name' => ['nullable', 'string', 'max:160'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'source' => ['required', 'string', Rule::in($this->sources())],
            'status' => ['required', 'string', Rule::in($this->statuses())],
            'payment_status' => ['required', 'string', Rule::in($this->paymentStatuses())],
            'fulfillment_status' => ['required', 'string', Rule::in($this->fulfillmentStatuses())],
            'currency' => ['required', 'string', 'size:3'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'placed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $connection = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereKey((int) $validated['provider_connection_id'])
            ->firstOrFail();

        $catalog = Catalog::query()
            ->where('workspace_id', $workspace->id)
            ->where('source', '!=', 'meta')
            ->where('status', 'active')
            ->whereKey((int) $validated['catalog_id'])
            ->firstOrFail();

        $collection = null;

        if (!empty($validated['catalog_collection_id'])) {
            $collection = CatalogCollection::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider_connection_id', $connection->id)
                ->whereKey((int) $validated['catalog_collection_id'])
                ->firstOrFail();
        }

        $productIds = array_values(array_unique(array_map('intval', (array) $validated['product_ids'])));

        $products = CatalogProduct::query()
            ->where('catalog_id', $catalog->id)
            ->where('is_active', true)
            ->whereIn('id', $productIds)
            ->orderBy('title')
            ->get();

        abort_if($products->isEmpty(), 422, 'Select at least one active product from the selected catalog.');

        $currency = strtoupper((string) $validated['currency']);
        $discountAmount = round((float) ($validated['discount_amount'] ?? 0), 2);
        $taxAmount = round((float) ($validated['tax_amount'] ?? 0), 2);
        $shippingAmount = round((float) ($validated['shipping_amount'] ?? 0), 2);

        $order = DB::transaction(function () use (
            $workspace,
            $request,
            $connection,
            $catalog,
            $collection,
            $products,
            $validated,
            $currency,
            $discountAmount,
            $taxAmount,
            $shippingAmount,
            $snapshotService
        ): CommerceOrder {
            $subtotal = round($products->sum(function (CatalogProduct $product): float {
                return (float) ($product->sale_price ?? $product->price ?? 0);
            }), 2);

            $total = round(max($subtotal - $discountAmount, 0) + $taxAmount + $shippingAmount, 2);

            $order = CommerceOrder::create([
                'workspace_id' => $workspace->id,
                'provider_connection_id' => $connection->id,
                'catalog_id' => $catalog->id,
                'catalog_collection_id' => $collection?->id,
                'external_order_id' => filled($validated['external_order_id'] ?? null) ? trim((string) $validated['external_order_id']) : null,
                'external_checkout_id' => filled($validated['external_checkout_id'] ?? null) ? trim((string) $validated['external_checkout_id']) : null,
                'source' => $validated['source'],
                'status' => $validated['status'],
                'payment_status' => $validated['payment_status'],
                'fulfillment_status' => $validated['fulfillment_status'],
                'customer_reference' => filled($validated['customer_reference'] ?? null) ? trim((string) $validated['customer_reference']) : null,
                'customer_name' => filled($validated['customer_name'] ?? null) ? trim((string) $validated['customer_name']) : null,
                'customer_email' => filled($validated['customer_email'] ?? null) ? trim((string) $validated['customer_email']) : null,
                'currency' => $currency,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $total,
                'is_test' => true,
                'placed_at' => !empty($validated['placed_at']) ? $validated['placed_at'] : now(),
                'meta' => [
                    'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                    'created_from' => 'workspace_commerce_orders',
                ],
            ]);

            foreach ($products as $product) {
                $price = round((float) ($product->sale_price ?? $product->price ?? 0), 2);

                $order->items()->create([
                    'catalog_product_id' => $product->id,
                    'sku' => $product->sku,
                    'title' => $product->title,
                    'quantity' => 1,
                    'currency' => $currency,
                    'unit_price' => $price,
                    'total_price' => $price,
                    'item_snapshot' => [
                        'catalog_id' => $product->catalog_id,
                        'title' => $product->title,
                        'description' => $product->description,
                        'image_url' => $product->image_url,
                        'product_url' => $product->product_url,
                        'price' => (float) ($product->price ?? 0),
                        'sale_price' => (float) ($product->sale_price ?? 0),
                        'currency' => $currency,
                    ],
                ]);
            }

            $snapshotService->capture($order, 'test_order_created', $request->user(), [
                'note' => 'Test order created from Commerce settings.',
            ]);

            return $order->fresh(['items', 'snapshots']);
        });

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Test order created with ' . $order->items->count() . ' item(s).');
    }

    public function updateStatus(
        Request $request,
        CommerceOrder $order,
        MetaCommerceOrderSnapshotService $snapshotService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceOrder($order, $workspace?->id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($this->statuses())],
            'payment_status' => ['required', 'string', Rule::in($this->paymentStatuses())],
            'fulfillment_status' => ['required', 'string', Rule::in($this->fulfillmentStatuses())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $meta = is_array($order->meta) ? $order->meta : [];
        $meta['last_status_note'] = filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null;
        $meta['last_status_updated_at'] = now()->toIso8601String();

        $order->update([
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'fulfillment_status' => $validated['fulfillment_status'],
            'meta' => $meta,
        ]);

        $snapshotService->capture($order->fresh(['items', 'providerConnection', 'catalog', 'collection']), 'status_updated', $request->user(), [
            'note' => $meta['last_status_note'] ?? null,
        ]);

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Order status updated.');
    }

    public function snapshot(
        Request $request,
        CommerceOrder $order,
        MetaCommerceOrderSnapshotService $snapshotService
    ): RedirectResponse {
        $workspace = $request->user()?->currentWorkspace();
        $this->guardWorkspaceOrder($order, $workspace?->id);

        $validated = $request->validate([
            'snapshot_type' => ['nullable', 'string', Rule::in(['manual', 'review_demo', 'sync_check'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $snapshotService->capture(
            $order->fresh(['items', 'providerConnection', 'catalog', 'collection']),
            $validated['snapshot_type'] ?? 'manual',
            $request->user(),
            [
                'note' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]
        );

        return redirect()
            ->route('settings.index', ['section' => 'commerce'])
            ->with('status', 'Order snapshot captured.');
    }

    protected function guardWorkspaceOrder(?CommerceOrder $order, ?int $workspaceId): void
    {
        if (! $workspaceId || ! $order || $order->workspace_id !== $workspaceId) {
            abort(404);
        }
    }

    protected function statuses(): array
    {
        return ['placed', 'confirmed', 'processing', 'fulfilled', 'cancelled', 'refunded'];
    }

    protected function paymentStatuses(): array
    {
        return ['pending', 'authorized', 'paid', 'failed', 'refunded'];
    }

    protected function fulfillmentStatuses(): array
    {
        return ['unfulfilled', 'processing', 'fulfilled', 'returned'];
    }

    protected function sources(): array
    {
        return ['manual_test', 'instagram_shop', 'website_checkout', 'catalog_share'];
    }
}
