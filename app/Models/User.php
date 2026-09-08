<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'job_title', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Keeps `slug` in sync with the display name for the author archive URL
     * (/tac-gia/{slug}). Booted rather than set in the controllers so every
     * creation path fills it — the web form, the installer's first admin,
     * a seeder, tinker — instead of only the ones we remembered to update.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->slug && ! $user->isDirty('name')) {
                return;
            }

            $base = Str::slug($user->name) ?: 'tac-gia';
            $slug = $base;
            $i = 2;

            while (static::where('slug', $slug)->whereKeyNot($user->getKey())->exists()) {
                $slug = $base.'-'.$i++;
            }

            $user->slug = $slug;
        });
    }

    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Author archive URL. Falls back to the primary key while `slug` is
     * still null (a row created between the migration and this model being
     * loaded) so the link never renders as /tac-gia/ with an empty segment.
     */
    public function url(): string
    {
        return route('author.show', $this->slug ?: $this->getKey());
    }

    /**
     * super_admin always passes — every other role is checked against its
     * capabilities (see database/migrations/..._create_capability_role_table.php
     * for the default role/capability matrix).
     */
    public function hasCapability(string $slug): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        return in_array($slug, $this->capabilitySlugs(), true);
    }

    private function capabilitySlugs(): array
    {
        return once(fn () => $this->assignedRole?->capabilities()->pluck('slug')->all() ?? []);
    }
}
