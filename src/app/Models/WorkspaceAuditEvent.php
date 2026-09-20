<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceAuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'actor_user_id',
        'event',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
