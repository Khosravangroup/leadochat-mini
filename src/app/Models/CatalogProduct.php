<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'is_active' => 'boolean',
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
}
