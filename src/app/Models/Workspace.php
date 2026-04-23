<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function providerConnections(): HasMany
    {
        return $this->hasMany(ProviderConnection::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }


    public function tags(): HasMany
    {
        return $this->hasMany(WorkspaceTag::class)->orderBy('sort_order')->orderBy('name');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(WorkspaceDepartment::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class)->orderBy('name');
    }

    public function catalogProductSets(): HasMany
    {
        return $this->hasMany(CatalogProductSet::class)->orderBy('name');
    }

    public function catalogCollections(): HasMany
    {
        return $this->hasMany(CatalogCollection::class)->orderBy('name');
    }
}
