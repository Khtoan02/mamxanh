<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'message', 'file', 'line', 'url'])]
class ErrorLog extends Model
{
    const UPDATED_AT = null;
}
