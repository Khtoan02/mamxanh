<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Capability;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['capabilities', 'users'])->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(),
            'capabilities' => Capability::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['array'],
            'capabilities.*' => ['string', 'exists:capabilities,slug'],
        ]);

        $slug = Str::slug($data['name']);

        if (Role::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Đã có vai trò với tên này.'])->withInput();
        }

        $role = Role::create([
            'slug' => $slug,
            'name' => $data['name'],
            'is_system' => false,
        ]);

        $role->capabilities()->sync(
            Capability::whereIn('slug', $data['capabilities'] ?? [])->pluck('id')
        );

        return redirect()->route('admin.roles.index')->with('status', 'Đã tạo vai trò.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'capabilities' => Capability::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->slug === 'super_admin', 403, 'Không thể chỉnh sửa vai trò Super Admin.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['array'],
            'capabilities.*' => ['string', 'exists:capabilities,slug'],
        ]);

        // Slug is never regenerated after creation — users.role stores it
        // as a string reference, so changing it would silently orphan them.
        if (! $role->is_system) {
            $role->name = $data['name'];
            $role->save();
        }

        $role->capabilities()->sync(
            Capability::whereIn('slug', $data['capabilities'] ?? [])->pluck('id')
        );

        return redirect()->route('admin.roles.index')->with('status', 'Đã cập nhật.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Không thể xoá vai trò hệ thống.']);
        }

        if (User::where('role', $role->slug)->exists()) {
            return back()->withErrors(['role' => 'Không thể xoá vai trò đang được gán cho người dùng.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Đã xoá.');
    }
}
