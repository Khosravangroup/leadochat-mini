<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialStory extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'provider',
        'provider_story_id',
        'media_url',
        'thumbnail_url',
        'posted_at',
        'expires_at',
        'status',
        'raw',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }
}