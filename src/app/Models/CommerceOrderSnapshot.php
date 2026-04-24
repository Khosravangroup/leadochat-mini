<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceOrderSnapshot extends Model
{
    protected $fillable = [
        'commerce_order_id',
        'created_by_user_id',
        'snapshot_type',
        'status',
        'captured_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CommerceOrder::class, 'commerce_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
