<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider',
        'provider_account_type',
        'provider_account_id',
        'external_oauth_user_id',
        'provider_account_name',
        'status',
        'connected_at',
        'last_synced_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(ProviderPermission::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class);
    }

    public function catalogProductSets(): HasMany
    {
        return $this->hasMany(CatalogProductSet::class);
    }
}
