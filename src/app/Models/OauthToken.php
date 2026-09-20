<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $fillable = [
        'provider_connection_id',
        'token_type',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }
}
