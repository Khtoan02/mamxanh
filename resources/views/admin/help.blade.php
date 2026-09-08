@extends('layouts.admin')

@section('title', 'Trợ giúp và Thông tin')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Trợ giúp và Thông tin</h2>

    <div class="grid lg:grid-cols-2 gap-5 mb-6">
        <x-card class="p-6">
            <h3 class="font-heading text-sm font-bold mb-4 flex items-center gap-2">
                <x-icon name="keyboard" class="h-4 w-4 text-muted-foreground" />
                Mẹo sử dụng nhanh
            </h3>
            <ul class="space-y-3 text-sm text-muted-foreground">
                <li class="flex gap-2.5">
                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-brand-green mt-0.5" />
                    Chuyển trang trong khu vực quản trị không tải lại toàn trang — điều hướng nhanh hơn nhờ Turbo Drive.
                </li>
                <li class="flex gap-2.5">
                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-brand-green mt-0.5" />
                    Vào <a href="{{ route('admin.health.index') }}" class="text-primary hover:underline">Sức khỏe Website</a> định kỳ để theo dõi hiệu năng, bảo mật, sao lưu và các cảnh báo thật.
                </li>
                <li class="flex gap-2.5">
                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-brand-green mt-0.5" />
                    Đổi logo, favicon, mạng xã hội, chế độ bảo trì... tất cả nằm trong <a href="{{ route('admin.settings.general') }}" class="text-primary hover:underline">Cài đặt</a>, chia theo từng tab.
                </li>
                <li class="flex gap-2.5">
                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-brand-green mt-0.5" />
                    Phân quyền chi tiết theo từng vai trò tại <a href="{{ route('admin.roles.index') }}" class="text-primary hover:underline">Vai trò</a> — không cần sửa code để thêm/bớt quyền.
                </li>
            </ul>
        </x-card>

        <x-card class="p-6">
            <h3 class="font-heading text-sm font-bold mb-4 flex items-center gap-2">
                <x-icon name="shield" class="h-4 w-4 text-muted-foreground" />
                Quyền của bạn
            </h3>
            <p class="text-sm text-muted-foreground mb-3">Vai trò hiện tại: <span class="font-medium text-foreground">{{ $roleName }}</span></p>
            @if ($capabilities->isEmpty())
                <p class="text-sm text-muted-foreground">Chưa có quyền nào được gán.</p>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($capabilities as $slug => $name)
                        <x-badge variant="secondary">{{ $name }}</x-badge>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <x-card class="p-6">
        <h3 class="font-heading text-sm font-bold mb-4 flex items-center gap-2">
            <x-icon name="book-open" class="h-4 w-4 text-muted-foreground" />
            Thông tin hệ thống
        </h3>
        <dl class="grid sm:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-xs text-muted-foreground">Laravel</dt>
                <dd class="font-medium">{{ $laravelVersion }}</dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">PHP</dt>
                <dd class="font-medium">{{ $phpVersion }}</dd>
            </div>
            <div>
                <dt class="text-xs text-muted-foreground">Môi trường</dt>
                <dd class="font-medium">{{ $environment }}</dd>
            </div>
        </dl>

        @if ($supportEmail)
            <div class="mt-5 pt-5 border-t border-border">
                <p class="text-sm text-muted-foreground">Cần hỗ trợ thêm? Liên hệ <a href="mailto:{{ $supportEmail }}" class="text-primary hover:underline">{{ $supportEmail }}</a></p>
            </div>
        @endif
    </x-card>
@endsection
