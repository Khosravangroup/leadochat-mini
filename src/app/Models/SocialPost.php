<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'provider',
        'provider_media_id',
        'media_type',
        'caption',
        'permalink',
        'media_url',
        'thumbnail_url',
        'posted_at',
        'comments_count',
        'like_count',
        'status',
        'raw',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'comments_count' => 'integer',
        'like_count' => 'integer',
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

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }

    public function mediaItems(): HasMany
    {
        return $this->hasMany(SocialPostMedia::class)
            ->orderBy('position')
            ->orderByDesc('is_cover')
            ->orderBy('id');
    }
}
