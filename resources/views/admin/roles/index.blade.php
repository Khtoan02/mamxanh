@extends('layouts.admin')

@section('title', 'Vai trò')

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90">
            + Thêm vai trò
        </a>
    </div>

    <x-card class="overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left text-xs text-muted-foreground">
                    <th class="px-4 py-3 font-medium">Tên</th>
                    <th class="px-4 py-3 font-medium">Slug</th>
                    <th class="px-4 py-3 font-medium">Quyền hạn</th>
                    <th class="px-4 py-3 font-medium">Người dùng</th>
                    <th class="px-4 py-3 font-medium text-right">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $role->name }}
                            @if ($role->is_system)
                                <x-badge variant="secondary" class="ml-2">Hệ thống</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $role->slug }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $role->slug === 'super_admin' ? 'Tất cả' : $role->capabilities_count }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3 text-sm">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-primary hover:underline">
                                    {{ $role->slug === 'super_admin' ? 'Xem' : 'Sửa' }}
                                </a>
                                @if (! $role->is_system)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Xoá vai trò {{ $role->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-destructive hover:underline">Xoá</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
@endsection
