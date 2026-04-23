<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogProductMarketOverride extends Model
{
    protected $fillable = [
        'catalog_product_id',
        'target_country',
        'content_language',
        'title',
        'description',
        'price',
        'sale_price',
        'currency',
        'product_url',
        'checkout_url',
        'google_product_category',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
