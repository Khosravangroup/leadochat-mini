<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkspaceTag extends Model
{
    private const DEFAULT_COLOR = '#6366f1';

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function color(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): string => $this->safeColor($value),
            set: fn (mixed $value): string => $this->safeColor($value),
        );
    }

    private function safeColor(mixed $value): string
    {
        $color = is_string($value) ? $value : '';

        return preg_match('/\A#[0-9A-Fa-f]{6}\z/', $color) === 1
            ? strtolower($color)
            : self::DEFAULT_COLOR;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_workspace_tag',
            'workspace_tag_id',
            'conversation_id'
        )->withTimestamps();
    }
}
