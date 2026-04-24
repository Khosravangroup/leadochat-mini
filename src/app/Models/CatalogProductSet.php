<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogProductSet extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'catalog_id',
        'external_product_set_id',
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProduct::class, 'catalog_product_set_items')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('catalog_product_set_items.sort_order')
            ->orderBy('catalog_product_set_items.id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(CatalogCollection::class, 'catalog_collection_product_set')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
