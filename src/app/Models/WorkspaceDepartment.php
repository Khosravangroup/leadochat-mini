<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceDepartment extends Model
{
    use HasFactory;

    private const DEFAULT_COLOR = '#6366f1';

    protected $fillable = [
        'workspace_id',
        'name',
        'color',
        'sort_order',
    ];

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

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'department_id');
    }
}
