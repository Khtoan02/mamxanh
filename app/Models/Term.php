<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['taxonomy', 'name', 'slug', 'parent_id', 'description'])]
class Term extends Model
{
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Term::class, 'parent_id');
    }

    public function scopeOfTaxonomy($query, string $taxonomy)
    {
        return $query->where('taxonomy', $taxonomy);
    }

    /**
     * Public archive URL, or null when this taxonomy has no archive route
     * registered (see TaxonomyArchiveController::PREFIXES). Templates MUST
     * check for null instead of assuming a link exists — otherwise a
     * plugin-registered taxonomy would render a guaranteed-404 link.
     */
    public function url(): ?string
    {
        $prefix = \App\Http\Controllers\TaxonomyArchiveController::prefixFor($this->taxonomy);

        return $prefix ? route('taxonomy.show', ['prefix' => $prefix, 'slug' => $this->slug]) : null;
    }
}
