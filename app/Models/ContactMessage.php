<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'message', 'visitor_id', 'status', 'notes'])]
class ContactMessage extends Model
{
    // Lightweight CRM pipeline for leads — no fake "deal value", just what's
    // actually tracked: has someone followed up, and what happened.
    const STATUS_LABELS = [
        'new' => 'Mới',
        'contacted' => 'Đang liên hệ',
        'won' => 'Đã chốt',
        'lost' => 'Không thành công',
    ];

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
