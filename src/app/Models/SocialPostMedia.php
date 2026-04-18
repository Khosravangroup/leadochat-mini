<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostMedia extends Model
{
    use HasFactory;

    protected $table = 'social_post_media';

    protected $fillable = [
        'social_post_id',
        'workspace_id',
        'provider_connection_id',
        'provider',
        'provider_media_id',
        'parent_provider_media_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'position',
        'is_cover',
        'posted_at',
        'raw',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_cover' => 'boolean',
        'posted_at' => 'datetime',
        'raw' => 'array',
    ];

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }
}
