<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogProduct extends Model
{
    protected $fillable = [
        'catalog_id',
        'external_product_id',
        'sku',
        'title',
        'description',
        'price',
        'currency',
        'image_url',
        'product_url',
        'brand',
        'product_condition',
        'inventory_quantity',
        'sale_price',
        'sale_price_effective_start_at',
        'sale_price_effective_end_at',
        'google_product_category',
        'content_language',
        'target_country',
        'availability',
        'is_active',
        'meta_sync_status',
        'meta_sync_error',
        'meta_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'inventory_quantity' => 'integer',
            'sale_price_effective_start_at' => 'datetime',
            'sale_price_effective_end_at' => 'datetime',
            'meta_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    public function productShares(): HasMany
    {
        return $this->hasMany(ConversationProductShare::class);
    }

    public function marketOverrides(): HasMany
    {
        return $this->hasMany(CatalogProductMarketOverride::class)
            ->orderBy('target_country')
            ->orderBy('content_language');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(CatalogProductOffer::class)
            ->orderBy('priority')
            ->orderByDesc('id');
    }

    public function productSets(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProductSet::class, 'catalog_product_set_items')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
