<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Catalog extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'source',
        'external_catalog_id',
        'external_business_id',
        'external_commerce_account_id',
        'name',
        'status',
        'meta_sync_status',
        'meta_sync_error',
        'last_synced_at',
        'meta_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
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

    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class);
    }

    public function productSets(): HasMany
    {
        return $this->hasMany(CatalogProductSet::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(CatalogCollection::class);
    }
}
