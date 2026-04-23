<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogCollection extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'catalog_id',
        'external_collection_id',
        'name',
        'description',
        'status',
        'meta_sync_status',
        'meta_sync_error',
        'meta_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta_synced_at' => 'datetime',
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

    public function metaCatalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProductSet::class, 'catalog_collection_product_set')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_collection_product_set.sort_order')
            ->orderBy('catalog_collection_product_set.id');
    }

    public function commerceOrders(): HasMany
    {
        return $this->hasMany(CommerceOrder::class, 'catalog_collection_id');
    }
}
