<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceOrder extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'catalog_id',
        'catalog_collection_id',
        'external_order_id',
        'external_checkout_id',
        'source',
        'status',
        'payment_status',
        'fulfillment_status',
        'customer_reference',
        'customer_name',
        'customer_email',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'is_test',
        'placed_at',
        'last_snapshot_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_test' => 'boolean',
            'placed_at' => 'datetime',
            'last_snapshot_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CatalogCollection::class, 'catalog_collection_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommerceOrderItem::class)
            ->orderBy('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(CommerceOrderSnapshot::class)
            ->orderByDesc('captured_at')
            ->orderByDesc('id');
    }
}
