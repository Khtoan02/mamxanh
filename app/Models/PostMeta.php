<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['post_id', 'meta_key', 'meta_value'])]
class PostMeta extends Model
{
    protected $table = 'post_meta';

    public $timestamps = false;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
