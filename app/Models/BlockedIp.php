<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ip_address', 'reason'])]
class BlockedIp extends Model
{
    const UPDATED_AT = null;
}
