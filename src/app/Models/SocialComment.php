<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'provider_connection_id',
        'social_post_id',
        'provider',
        'provider_media_id',
        'provider_comment_id',
        'parent_provider_comment_id',
        'provider_user_id',
        'username',
        'text',
        'status',
        'is_hidden',
        'commented_at',
        'replied_publicly_at',
        'replied_via_dm_at',
        'raw',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'commented_at' => 'datetime',
        'replied_publicly_at' => 'datetime',
        'replied_via_dm_at' => 'datetime',
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

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_provider_comment_id', 'provider_comment_id');
    }

    public function childComments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_provider_comment_id', 'provider_comment_id');
    }
}
