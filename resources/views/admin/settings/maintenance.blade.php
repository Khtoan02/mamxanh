@extends('layouts.admin')

@section('title', 'Cài đặt — Bảo trì')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'maintenance'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <x-card class="p-6 max-w-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-heading text-sm font-bold flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full {{ $isDown ? 'bg-destructive' : 'bg-brand-green' }}"></span>
                    {{ $isDown ? 'Đang bảo trì' : 'Đang hoạt động bình thường' }}
                </h2>
                <p class="text-xs text-muted-foreground mt-2 max-w-md">
                    @if ($isDown)
                        Khách truy cập trang public sẽ thấy trang "Đang bảo trì" (mã 503). Bạn vẫn đăng nhập và quản lý được ở <code>/admin</code> như bình thường.
                    @else
                        Bật chế độ bảo trì khi cần tạm ẩn website public để nâng cấp/sửa lỗi lớn — trang quản trị vẫn hoạt động bình thường cho bạn trong lúc đó.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('admin.settings.maintenance.toggle') }}" class="shrink-0">
                @csrf
                <x-button type="submit" :variant="$isDown ? 'primary' : 'outline'" class="!w-auto px-5">
                    {{ $isDown ? 'Tắt bảo trì' : 'Bật bảo trì' }}
                </x-button>
            </form>
        </div>
    </x-card>
@endsection
