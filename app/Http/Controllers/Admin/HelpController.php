<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Capability;

class HelpController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('admin.help', [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'environment' => config('app.env'),
            'roleName' => $user->assignedRole?->name ?? $user->role,
            'capabilities' => $user->role === 'super_admin'
                ? Capability::pluck('name', 'slug')
                : ($user->assignedRole?->capabilities()->pluck('name', 'slug') ?? collect()),
            'supportEmail' => env('SITE_CONTACT_EMAIL'),
        ]);
    }
}
