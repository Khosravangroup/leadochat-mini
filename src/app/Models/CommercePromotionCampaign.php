<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercePromotionCampaign extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'catalog_collection_id',
        'social_post_id',
        'campaign_type',
        'objective',
        'name',
        'description',
        'status',
        'call_to_action',
        'destination_url',
        'external_campaign_id',
        'external_ad_set_id',
        'external_ad_id',
        'budget_amount',
        'currency',
        'starts_at',
        'ends_at',
        'meta_sync_status',
        'meta_sync_error',
        'meta_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CatalogCollection::class, 'catalog_collection_id');
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }
}
