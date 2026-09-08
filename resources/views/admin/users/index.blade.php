@extends('layouts.admin')

@section('title', 'Người dùng')

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90">
            + Thêm tài khoản
        </a>
    </div>

    <x-card class="overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left text-xs text-muted-foreground">
                    <th class="px-4 py-3 font-medium">Tên</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Đăng nhập gần nhất</th>
                    <th class="px-4 py-3 font-medium text-right">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $user->email }}</td>
                        <td class="px-4 py-3"><x-badge variant="default">{{ $user->assignedRole?->name ?? $user->role }}</x-badge></td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3 text-sm">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-primary hover:underline">Sửa</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Xoá {{ $user->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-destructive hover:underline">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
