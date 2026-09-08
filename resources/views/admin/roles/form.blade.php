@extends('layouts.admin')

@php
    $isSuperAdmin = $role->slug === 'super_admin';
    $nameLocked = $role->exists && $role->is_system;
    $assignedSlugs = old('capabilities', $role->exists ? $role->capabilitySlugs : []);
@endphp

@section('title', $role->exists ? 'Sửa vai trò' : 'Thêm vai trò')

@section('content')
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    @if ($isSuperAdmin)
        <x-alert variant="default" class="mb-5">
            Super Admin luôn có toàn bộ quyền hạn và không thể chỉnh sửa — vai trò này được hệ thống bảo vệ.
        </x-alert>
    @endif

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6 max-w-2xl">
        @csrf
        @if ($role->exists)
            @method('PUT')
        @endif

        <x-card class="p-6">
            <x-label for="name">Tên vai trò</x-label>
            <x-input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required :disabled="$isSuperAdmin || $nameLocked" />
            @if ($nameLocked && ! $isSuperAdmin)
                <p class="mt-1.5 text-xs text-muted-foreground">Tên vai trò hệ thống không thể đổi.</p>
            @endif
        </x-card>

        <x-card class="p-6">
            <p class="mb-3 text-sm font-medium text-foreground">Quyền hạn</p>
            <div class="space-y-2.5">
                @foreach ($capabilities as $capability)
                    <label class="flex items-start gap-2.5 text-sm {{ $isSuperAdmin ? 'opacity-60' : '' }}">
                        <input
                            type="checkbox"
                            name="capabilities[]"
                            value="{{ $capability->slug }}"
                            @checked(in_array($capability->slug, $assignedSlugs, true))
                            @disabled($isSuperAdmin)
                            class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-ring"
                        >
                        <span>
                            <span class="font-medium text-foreground">{{ $capability->name }}</span>
                            <span class="text-muted-foreground"> — {{ $capability->slug }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </x-card>

        @unless ($isSuperAdmin)
            <div class="flex gap-3">
                <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu</x-button>
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors">Huỷ</a>
            </div>
        @else
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors">← Quay lại</a>
        @endunless
    </form>
@endsection
