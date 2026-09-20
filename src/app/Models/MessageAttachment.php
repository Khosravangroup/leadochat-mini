<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'attachment_type',
        'url',
        'thumbnail_url',
        'mime_type',
        'file_name',
        'file_size',
        'width',
        'height',
        'duration_seconds',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getDisplayUrlAttribute(): ?string
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $disk = (string) ($meta['disk'] ?? '');
        $path = (string) ($meta['path'] ?? '');

        if (
            in_array($disk, ['local', 'public'], true)
            && str_starts_with($path, 'message-attachments/')
        ) {
            return route('attachments.show', $this);
        }

        return $this->url;
    }
}
