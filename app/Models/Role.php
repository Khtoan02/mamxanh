<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'is_system'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function capabilities(): BelongsToMany
    {
        return $this->belongsToMany(Capability::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function hasCapability(string $slug): bool
    {
        return $this->capabilities()->where('slug', $slug)->exists();
    }

    protected function capabilitySlugs(): Attribute
    {
        return Attribute::get(fn () => $this->capabilities()->pluck('slug')->all());
    }
}
