@extends('layouts.admin')

@section('title', 'Marketing')

@php
    $dotClass = ['pass' => 'bg-brand-green', 'warn' => 'bg-amber-500', 'fail' => 'bg-destructive'];
@endphp

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Marketing</h2>

    <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-1">Hiệu suất nội dung theo loại</p>
            <p class="text-xs text-muted-foreground mb-4">Lượt xem 30 ngày qua, theo từng loại nội dung</p>
            <x-donut-chart :data="$contentPerformance" value-key="views" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-1">Nguồn dẫn lead (lượt chạm đầu)</p>
            <p class="text-xs text-muted-foreground mb-4">Kênh đưa khách đến LẦN ĐẦU, trước khi họ gửi liên hệ (toàn bộ lịch sử)</p>
            <x-donut-chart :data="$leadSources" label-key="source" value-key="count" :colors="['var(--color-brand-green)', 'var(--color-primary)', '#f59e0b', '#0ea5e9', '#a855f7', '#64748b']" />
        </x-card>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-1">Nguồn dẫn lead (lượt chạm cuối)</p>
            <p class="text-xs text-muted-foreground mb-4">Kênh ngay trước khi khách gửi liên hệ — khác lượt chạm đầu ở trên</p>
            <x-donut-chart :data="$lastTouchLeadSources" label-key="source" value-key="count" :colors="['var(--color-brand-green)', 'var(--color-primary)', '#f59e0b', '#0ea5e9', '#a855f7', '#64748b']" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-1">Phân nhóm kênh (30 ngày)</p>
            <p class="text-xs text-muted-foreground mb-4">Trực tiếp / Tìm kiếm / Mạng xã hội / Giới thiệu — tự phân loại từ referrer</p>
            <x-donut-chart :data="$channels" label-key="channel" value-key="views" />
        </x-card>
    </div>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-1">Tỉ lệ chuyển đổi theo kênh</p>
        <p class="text-xs text-muted-foreground mb-4">% khách lần đầu ghé qua kênh này rồi trở thành lead (toàn bộ lịch sử)</p>
        <x-hbar-chart :data="$channelConversion" label-key="channel" value-key="rate" pct-key="rate" suffix="%" color="var(--color-brand-green)" />
        <div class="overflow-x-auto mt-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-muted-foreground">
                        <th class="pb-2 pr-4 font-medium">Kênh</th>
                        <th class="pb-2 pr-4 font-medium">Khách (lượt chạm đầu)</th>
                        <th class="pb-2 pr-4 font-medium">Lead</th>
                        <th class="pb-2 pr-4 font-medium">Tỉ lệ chuyển đổi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($channelConversion as $row)
                        <tr class="border-t border-border">
                            <td class="py-2 pr-4">{{ $row['channel'] }}</td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ number_format($row['visitors']) }}</td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ number_format($row['leads']) }}</td>
                            <td class="py-2 pr-4 font-medium">{{ number_format($row['rate'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-1">Hiệu suất trang đích theo chiến dịch (UTM)</p>
        <p class="text-xs text-muted-foreground mb-4">Chiến dịch nào thu hút traffic tốt nhất, và trang đích chính của mỗi chiến dịch</p>
        @if ($campaigns->isEmpty())
            <p class="text-sm text-muted-foreground">Chưa có lượt truy cập nào mang tham số <code>utm_campaign</code>. Thêm tham số này vào link quảng cáo/chiến dịch để bắt đầu theo dõi.</p>
        @else
            @php
                $campaignRows = $campaigns->map(fn (array $row) => [
                    ...$row,
                    'landing' => "Trang đích chính: {$row['topPage']} ({$row['topPageViews']} lượt)",
                ]);
            @endphp
            <x-hbar-chart :data="$campaignRows" label-key="campaign" value-key="views" sublabel-key="landing" suffix=" lượt" color="var(--color-primary)" />
        @endif
    </x-card>

    <x-card class="p-5">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-semibold">Sức khoẻ Marketing &amp; SEO</p>
            <a href="{{ route('admin.health.index') }}" class="text-xs text-primary hover:underline">Xem đầy đủ Sức khỏe Website →</a>
        </div>
        <div class="space-y-3">
            @foreach ($seoChecks as $check)
                <div class="flex items-center justify-between gap-4 text-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $dotClass[$check['status']] }}"></span>
                        <span class="text-foreground">{{ $check['label'] }}</span>
                    </div>
                    <span class="text-xs text-muted-foreground text-right">{{ $check['detail'] }}</span>
                </div>
            @endforeach
        </div>
    </x-card>
@endsection
