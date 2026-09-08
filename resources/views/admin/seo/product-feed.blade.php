@extends('layouts.admin')

@section('title', 'SEO — Feed sản phẩm')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-1">SEO &amp; GEO</h2>
    <p class="text-sm text-muted-foreground mb-6">Thiết lập mẫu tiêu đề/mô tả chung cho toàn bộ site.</p>

    @include('admin.seo._tabs', ['active' => 'product-feed'])

    <x-card class="p-6 space-y-4 max-w-2xl">
        <h2 class="font-heading text-sm font-bold">Feed Google Merchant Center</h2>
        <p class="text-xs text-muted-foreground leading-relaxed">
            Chỉ áp dụng cho sản phẩm bán trực tiếp (không phải sản phẩm affiliate có link mua ngoài) — cần có ảnh đại diện và giá là 1 con số thật (không nhận "Liên hệ"). Đăng ký tại
            <a href="https://merchants.google.com" target="_blank" class="text-primary hover:underline">merchants.google.com</a> (miễn phí, tự tạo tài khoản) → xác minh quyền sở hữu site → mục "Feeds" (Nguồn dữ liệu) → dán URL bên dưới.
        </p>
        <div>
            <x-label>URL feed</x-label>
            <div class="flex items-center gap-2">
                <code class="flex-1 rounded-lg border border-border bg-muted px-3 py-2 text-xs break-all">{{ $feedUrl }}</code>
                <a href="{{ $feedUrl }}" target="_blank" class="shrink-0 rounded-lg border border-border px-3 py-2 text-xs font-medium hover:bg-accent">Xem</a>
            </div>
        </div>
    </x-card>

    <x-card class="p-6 space-y-3 max-w-2xl mt-5">
        <h2 class="font-heading text-sm font-bold">Trạng thái</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-2xl font-heading font-extrabold text-brand-green">{{ $stats['eligible'] }}</p>
                <p class="text-xs text-muted-foreground">Đủ điều kiện, đang có trong feed</p>
            </div>
            <div>
                <p class="text-2xl font-heading font-extrabold">{{ $stats['total'] }}</p>
                <p class="text-xs text-muted-foreground">Tổng số sản phẩm đã đăng</p>
            </div>
        </div>
        @if ($stats['affiliateExcluded'] || $stats['missingPrice'] || $stats['missingImage'])
            <div class="pt-3 border-t border-border space-y-1.5 text-xs text-muted-foreground">
                <p class="font-semibold text-foreground">Bị loại khỏi feed:</p>
                @if ($stats['affiliateExcluded'])
                    <p>{{ $stats['affiliateExcluded'] }} sản phẩm affiliate (có link mua ngoài)</p>
                @endif
                @if ($stats['missingPrice'])
                    <p>{{ $stats['missingPrice'] }} sản phẩm chưa có giá dạng số (vd đang để "Liên hệ")</p>
                @endif
                @if ($stats['missingImage'])
                    <p>{{ $stats['missingImage'] }} sản phẩm chưa có ảnh đại diện</p>
                @endif
            </div>
        @endif
    </x-card>
@endsection
