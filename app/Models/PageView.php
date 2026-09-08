<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['path', 'visitor_id', 'referrer_host', 'device_type', 'os', 'browser', 'language', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'])]
class PageView extends Model
{
    const UPDATED_AT = null;
}
