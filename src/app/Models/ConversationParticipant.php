<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'provider_user_id',
        'display_name',
        'handle',
        'avatar_url',
        'role',
        'is_self',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_self' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_participant_id');
    }
}
