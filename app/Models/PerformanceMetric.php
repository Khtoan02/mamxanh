<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['metric', 'value', 'rating', 'path', 'device_type'])]
class PerformanceMetric extends Model
{
    const UPDATED_AT = null;
}
