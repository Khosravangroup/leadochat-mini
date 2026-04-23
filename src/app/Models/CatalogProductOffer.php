<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CatalogProductOffer extends Model
{
    protected $fillable = [
        'catalog_product_id',
        'catalog_product_market_override_id',
        'name',
        'status',
        'discount_type',
        'discount_value',
        'currency',
        'priority',
        'starts_at',
        'ends_at',
        'checkout_url',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }

    public function marketOverride(): BelongsTo
    {
        return $this->belongsTo(CatalogProductMarketOverride::class, 'catalog_product_market_override_id');
    }

    public function isActiveNow(?Carbon $moment = null): bool
    {
        $moment = $moment ?? now();

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->gt($moment)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($moment)) {
            return false;
        }

        return true;
    }

    public function resolvedSalePrice(?float $basePrice): ?float
    {
        if ($basePrice === null) {
            return null;
        }

        $discountValue = (float) $this->discount_value;

        $salePrice = match ($this->discount_type) {
            'percentage' => $basePrice - ($basePrice * ($discountValue / 100)),
            'fixed_amount' => $basePrice - $discountValue,
            default => $discountValue,
        };

        return round(max($salePrice, 0), 2);
    }
}
