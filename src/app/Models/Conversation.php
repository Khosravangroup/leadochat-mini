<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'workspace_id',
        'department_id',
        'assigned_user_id',
        'provider_connection_id',
        'provider',
        'provider_conversation_id',
        'type',
        'title',
        'avatar_url',
        'status',
        'last_message_preview',
        'internal_note',
        'tags',
        'unread_count',
        'is_archived',
        'is_muted',
        'last_message_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_archived' => 'boolean',
            'is_muted' => 'boolean',
            'last_message_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkspaceDepartment::class, 'department_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function customerParticipant(): HasOne
    {
        return $this->hasOne(ConversationParticipant::class)
            ->where('is_self', false);
    }

    public function selfParticipant(): HasOne
    {
        return $this->hasOne(ConversationParticipant::class)
            ->where('is_self', true);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('id');
    }

    public function workspaceTags(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceTag::class,
            'conversation_workspace_tag',
            'conversation_id',
            'workspace_tag_id'
        )->withTimestamps()->orderBy('sort_order')->orderBy('name');
    }
}
