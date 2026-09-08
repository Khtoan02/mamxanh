@extends('layouts.admin')

@section('title', 'Phân tích')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-heading text-lg font-bold">Phân tích truy cập</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-green animate-pulse"></span>
                    {{ $liveVisitors }} đang online
                </span>
            </p>
        </div>
        <div class="flex gap-1 rounded-lg border border-border bg-muted p-1">
            @foreach ([7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày'] as $days => $label)
                <a href="{{ route('admin.analytics', ['range' => $days]) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $days ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Lượt xem</p>
                <x-icon name="eye" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($views) }}</p>
            <div class="mt-2"><x-trend-pill :delta="$viewsDelta" /></div>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Người xem duy nhất</p>
                <x-icon name="users-round" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($uniqueVisitors) }}</p>
            <div class="mt-2"><x-trend-pill :delta="$uniqueVisitorsDelta" /></div>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Khách đang online</p>
                <x-icon name="activity" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($liveVisitors) }}</p>
            <p class="mt-2 text-[11px] text-muted-foreground">5 phút gần nhất</p>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Admin đang online</p>
                <x-icon name="user-check" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($adminsOnline) }}</p>
            <p class="mt-2 text-[11px] text-muted-foreground">5 phút gần nhất</p>
        </x-card>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Số phiên</p>
                <x-icon name="repeat" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($sessionInsights['count']) }}</p>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Thời lượng phiên TB</p>
                <x-icon name="clock" class="h-4 w-4 text-muted-foreground" />
            </div>
            @php
                $mins = intdiv($sessionInsights['avgDurationSeconds'], 60);
                $secs = $sessionInsights['avgDurationSeconds'] % 60;
            @endphp
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ $mins }}m {{ $secs }}s</p>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Trang / phiên TB</p>
                <x-icon name="file-text" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($sessionInsights['avgPagesPerSession'], 1) }}</p>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Tỉ lệ thoát trang</p>
                <x-icon name="log-out" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ $sessionInsights['bounceRatePct'] }}%</p>
        </x-card>
    </div>

    <x-card class="p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-semibold">Xu hướng lượt xem</p>
            <p class="text-xs text-muted-foreground">Cao nhất: {{ number_format($chart['maxViews']) }} lượt/ngày</p>
        </div>
        <x-area-chart :chart="$chart" />
    </x-card>

    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <x-card class="p-5 lg:col-span-2">
            <p class="text-sm font-semibold mb-4">Trang xem nhiều nhất</p>
            <x-hbar-chart :data="$topPages" label-key="path" value-key="views" color="var(--color-primary)" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Phân bổ thiết bị</p>
            <x-donut-chart :data="$devices" value-key="views" :colors="['var(--color-primary)', 'var(--color-brand-green)', '#f59e0b']" />
        </x-card>
    </div>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-4">Nguồn giới thiệu hàng đầu</p>
        @if ($topReferrers->isEmpty())
            <p class="text-sm text-muted-foreground">Chưa có lượt truy cập từ nguồn ngoài.</p>
        @else
            <x-hbar-chart :data="$topReferrers" label-key="referrer_host" value-key="views" color="var(--color-primary)" />
        @endif
    </x-card>

    <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Khách mới vs quay lại</p>
            <x-donut-chart :data="[
                ['label' => 'Khách mới', 'views' => $newVsReturning['new'], 'pct' => $newVsReturning['newPct']],
                ['label' => 'Quay lại', 'views' => $newVsReturning['returning'], 'pct' => $newVsReturning['returningPct']],
            ]" value-key="views" :colors="['var(--color-primary)', 'var(--color-brand-green)']" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Tần suất quay lại</p>
            <x-donut-chart :data="$frequency" value-key="count" :colors="['var(--color-primary)', 'var(--color-brand-green)', '#f59e0b']" />
        </x-card>
    </div>

    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="monitor" class="h-4 w-4 text-muted-foreground" />Hệ điều hành</p>
            <x-donut-chart :data="$os" value-key="views" size="112" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="globe" class="h-4 w-4 text-muted-foreground" />Trình duyệt</p>
            <x-donut-chart :data="$browsers" value-key="views" size="112" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="tag" class="h-4 w-4 text-muted-foreground" />Ngôn ngữ</p>
            <x-donut-chart :data="$languages" value-key="views" size="112" />
        </x-card>
    </div>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="zap" class="h-4 w-4 text-muted-foreground" />Giờ cao điểm truy cập</p>
        <div class="flex items-end gap-1 h-24">
            @foreach ($peakHours as $h)
                <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                    <div class="w-full rounded-t bg-primary/70 group-hover:bg-primary transition-colors" style="height: {{ max(2, $h['pct']) }}%" title="{{ $h['hour'] }}h — {{ number_format($h['count']) }} lượt xem"></div>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between mt-1 text-[10px] text-muted-foreground">
            <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
        </div>
    </x-card>

    <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="log-in" class="h-4 w-4 text-muted-foreground" />Trang vào đầu tiên</p>
            <x-hbar-chart :data="$sessionInsights['entryPages']" label-key="path" value-key="count" color="var(--color-primary)" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="log-out" class="h-4 w-4 text-muted-foreground" />Trang thoát cuối</p>
            <x-hbar-chart :data="$sessionInsights['exitPages']" label-key="path" value-key="count" color="var(--color-brand-green)" />
        </x-card>
    </div>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-4 flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4 text-muted-foreground" />Giữ chân theo nhóm tuần (Cohort retention)</p>
        @if (empty($cohorts))
            <p class="text-sm text-muted-foreground">Chưa đủ dữ liệu để tính giữ chân theo nhóm.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-muted-foreground">
                            <th class="pb-2 pr-4 font-medium">Tuần bắt đầu</th>
                            <th class="pb-2 pr-4 font-medium">Số khách mới</th>
                            @for ($w = 0; $w <= 4; $w++)
                                <th class="pb-2 pr-4 font-medium">Tuần +{{ $w }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cohorts as $c)
                            <tr class="border-t border-border">
                                <td class="py-2 pr-4 whitespace-nowrap">{{ $c['week'] }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap text-muted-foreground">{{ number_format($c['size']) }}</td>
                                @for ($w = 0; $w <= 4; $w++)
                                    <td class="py-2 pr-4 whitespace-nowrap">
                                        @if (isset($c['retention'][$w]))
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium" style="background: color-mix(in srgb, var(--brand-green) {{ $c['retention'][$w]['pct'] }}%, transparent); color: {{ $c['retention'][$w]['pct'] > 40 ? 'white' : 'inherit' }}">
                                                {{ $c['retention'][$w]['pct'] }}%
                                            </span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card class="p-5 mb-6">
        <p class="text-sm font-semibold mb-4">Tăng trưởng so cùng kỳ</p>
        <div class="grid sm:grid-cols-3 gap-6">
            <div>
                <p class="text-xs text-muted-foreground mb-1">Tháng này vs tháng trước</p>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-heading font-bold">{{ number_format($growth['month']['current']) }}</span>
                    <x-trend-pill :delta="$growth['month']['delta']" />
                </div>
                <x-compare-bars :current="$growth['month']['current']" :previous="$growth['month']['previous']" current-label="Tháng này" previous-label="Tháng trước" />
            </div>
            <div>
                <p class="text-xs text-muted-foreground mb-1">Quý này vs quý trước</p>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-heading font-bold">{{ number_format($growth['quarter']['current']) }}</span>
                    <x-trend-pill :delta="$growth['quarter']['delta']" />
                </div>
                <x-compare-bars :current="$growth['quarter']['current']" :previous="$growth['quarter']['previous']" current-label="Quý này" previous-label="Quý trước" color="var(--color-brand-green)" />
            </div>
            <div>
                <p class="text-xs text-muted-foreground mb-1">Năm nay vs năm trước</p>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-heading font-bold">{{ number_format($growth['year']['current']) }}</span>
                    <x-trend-pill :delta="$growth['year']['delta']" />
                </div>
                <x-compare-bars :current="$growth['year']['current']" :previous="$growth['year']['previous']" current-label="Năm nay" previous-label="Năm trước" color="#f59e0b" />
            </div>
        </div>
    </x-card>

    <x-card class="p-5">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-semibold flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-green animate-pulse"></span>
                Luồng hoạt động gần đây
            </p>
            <span class="text-[11px] text-muted-foreground">Tự làm mới mỗi 20s</span>
        </div>
        <div id="recent-activity-feed" class="space-y-2 text-sm" data-url="{{ route('admin.analytics.recent-activity') }}">
            @forelse ($recentActivity as $item)
                <div class="flex items-center justify-between border-b border-border/60 pb-2 last:border-0 last:pb-0">
                    <span class="truncate text-foreground">{{ $item['path'] }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground ml-2">{{ $item['time'] }}</span>
                </div>
            @empty
                <p class="text-sm text-muted-foreground">Chưa có hoạt động trong 30 phút qua.</p>
            @endforelse
        </div>
    </x-card>

    @push('scripts')
        <script>
            (function () {
                var feed = document.getElementById('recent-activity-feed');
                if (!feed) return;

                var timer = setInterval(function () {
                    if (!document.body.contains(feed)) {
                        clearInterval(timer);
                        return;
                    }

                    fetch(feed.dataset.url, { headers: { Accept: 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.items || !data.items.length) {
                                feed.innerHTML = '<p class="text-sm text-muted-foreground">Chưa có hoạt động trong 30 phút qua.</p>';
                                return;
                            }

                            feed.innerHTML = data.items.map(function (item) {
                                var path = document.createElement('div');
                                path.textContent = item.path;
                                return '<div class="flex items-center justify-between border-b border-border/60 pb-2 last:border-0 last:pb-0">'
                                    + '<span class="truncate text-foreground">' + path.innerHTML + '</span>'
                                    + '<span class="shrink-0 text-xs text-muted-foreground ml-2">' + item.time + '</span>'
                                    + '</div>';
                            }).join('');
                        })
                        .catch(function () {});
                }, 20000);
            })();
        </script>
    @endpush
@endsection
