<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EnvironmentWriter;
use App\Support\ThemeRegistry;

class ThemeController extends Controller
{
    public function index(ThemeRegistry $themes)
    {
        return view('admin.themes.index', [
            'themes' => $themes->all(),
            'active' => config('theme.active', 'default'),
        ]);
    }

    public function activate(string $theme, ThemeRegistry $themes, EnvironmentWriter $env)
    {
        abort_unless($themes->get($theme), 404);

        $env->set(['ACTIVE_THEME' => $theme]);

        return redirect()->route('admin.themes.index')->with('status', 'Đã kích hoạt giao diện.');
    }
}
