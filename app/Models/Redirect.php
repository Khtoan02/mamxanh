<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source', 'target', 'match_type', 'status_code', 'is_active'])]
class Redirect extends Model
{
    public const MATCH_TYPES = [
        'exact' => 'Khớp chính xác',
        'contains' => 'Chứa đoạn này',
        'start' => 'Bắt đầu bằng',
        'end' => 'Kết thúc bằng',
        'regex' => 'Biểu thức chính quy (regex)',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Real path matching per `match_type` — mirrors the match modes an
     * actual redirect manager needs (Rank Math's redirection module has the
     * same 5 modes), not just a single exact-match shortcut.
     */
    public function matches(string $path): bool
    {
        $source = ltrim($this->source, '/');
        $path = ltrim($path, '/');

        return match ($this->match_type) {
            'contains' => str_contains($path, $source),
            'start' => str_starts_with($path, $source),
            'end' => str_ends_with($path, $source),
            'regex' => @preg_match('#'.$this->source.'#', $path) === 1,
            default => $path === $source,
        };
    }
}
