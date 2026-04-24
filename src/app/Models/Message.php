<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_participant_id',
        'reply_to_message_id',
        'provider',
        'provider_message_id',
        'provider_reply_to_message_id',
        'direction',
        'message_type',
        'text_body',
        'caption',
        'status',
        'sent_at',
        'received_at',
        'read_at',
        'failed_at',
        'last_error',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function senderParticipant(): BelongsTo
    {
        return $this->belongsTo(ConversationParticipant::class, 'sender_participant_id');
    }

    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function productShare(): HasOne
    {
        return $this->hasOne(ConversationProductShare::class);
    }
}
