<?php

namespace App\Services\Meta\Commerce;

use App\Models\CommerceOrder;
use App\Models\CommerceOrderSnapshot;
use App\Models\User;

class MetaCommerceOrderSnapshotService
{
    public function capture(
        CommerceOrder $order,
        string $snapshotType = 'manual',
        ?User $actor = null,
        array $extra = []
    ): CommerceOrderSnapshot {
        $order->loadMissing([
            'providerConnection',
            'catalog',
            'collection',
            'items.product.catalog',
        ]);

        $snapshot = $order->snapshots()->create([
            'created_by_user_id' => $actor?->id,
            'snapshot_type' => $snapshotType,
            'status' => $order->status,
            'captured_at' => now(),
            'payload' => array_merge($this->buildPayload($order), $extra),
        ]);

        $order->update([
            'last_snapshot_at' => $snapshot->captured_at,
        ]);

        return $snapshot;
    }

    public function buildPayload(CommerceOrder $order): array
    {
        return [
            'captured_at' => now()->toIso8601String(),
            'order' => [
                'id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'external_checkout_id' => $order->external_checkout_id,
                'source' => $order->source,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'fulfillment_status' => $order->fulfillment_status,
                'is_test' => $order->is_test,
                'placed_at' => optional($order->placed_at)->toIso8601String(),
                'customer_reference' => $order->customer_reference,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'currency' => $order->currency,
                'subtotal_amount' => (float) $order->subtotal_amount,
                'discount_amount' => (float) $order->discount_amount,
                'tax_amount' => (float) $order->tax_amount,
                'shipping_amount' => (float) $order->shipping_amount,
                'total_amount' => (float) $order->total_amount,
            ],
            'connection' => [
                'provider_connection_id' => $order->provider_connection_id,
                'provider_account_id' => $order->providerConnection?->provider_account_id,
                'provider_account_name' => $order->providerConnection?->provider_account_name,
            ],
            'catalog' => [
                'catalog_id' => $order->catalog_id,
                'catalog_name' => $order->catalog?->name,
                'collection_id' => $order->catalog_collection_id,
                'collection_name' => $order->collection?->name,
            ],
            'items' => $order->items->map(fn ($item) => [
                'catalog_product_id' => $item->catalog_product_id,
                'sku' => $item->sku,
                'title' => $item->title,
                'quantity' => $item->quantity,
                'currency' => $item->currency,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'product_snapshot' => $item->item_snapshot,
            ])->values()->all(),
        ];
    }
}
