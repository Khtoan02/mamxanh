@extends('layouts.admin')

@section('title', 'Sức khỏe Website')

@php
    $statusIcon = ['pass' => 'check-circle', 'warn' => 'alert-triangle', 'fail' => 'x-circle'];
    $statusColor = ['pass' => 'text-brand-green', 'warn' => 'text-amber-500', 'fail' => 'text-destructive'];
    $bandHex = ['excellent' => 'var(--color-brand-green)', 'good' => '#f59e0b', 'poor' => 'var(--color-destructive)'];
    $bandText = ['excellent' => 'text-brand-green', 'good' => 'text-amber-600', 'poor' => 'text-destructive'];
    $pillarMeta = [
        'Hiệu năng & Tốc độ tải trang' => ['icon' => 'zap', 'id' => 'pillar-hieu-nang'],
        'Bảo mật & An toàn thông tin' => ['icon' => 'shield', 'id' => 'pillar-bao-mat'],
        'Hạ tầng & Độ sẵn sàng' => ['icon' => 'server', 'id' => 'pillar-ha-tang'],
        'SEO kỹ thuật & Khả năng cào dữ liệu' => ['icon' => 'search', 'id' => 'pillar-seo'],
        'Trải nghiệm người dùng & Tiếp cận' => ['icon' => 'eye', 'id' => 'pillar-ux'],
        'Chất lượng nội dung' => ['icon' => 'file-text', 'id' => 'pillar-content'],
        'Tích hợp & Theo dõi dữ liệu' => ['icon' => 'plug', 'id' => 'pillar-integration'],
    ];
    $statusPoints = fn ($status) => match ($status) {
        'pass' => 100,
        'warn' => 50,
        default => 0,
    };
    $severityOrder = fn ($status) => match ($status) {
        'fail' => 0,
        'warn' => 1,
        default => 2,
    };
@endphp

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    {{-- Overall health score --}}
    <x-card class="p-6 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="relative h-20 w-20 shrink-0 rounded-full" style="background: conic-gradient({{ $bandHex[$score['band']] }} {{ $score['score'] * 3.6 }}deg, var(--color-muted) 0)">
                    <div class="absolute inset-2 rounded-full bg-card flex items-center justify-center">
                        <span class="text-xl font-heading font-extrabold">{{ $score['score'] }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Điểm sức khỏe tổng thể</p>
                    <p class="text-lg font-heading font-bold {{ $bandText[$score['band']] }}">{{ $score['label'] }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">Tổng hợp từ {{ $summary['pass'] + $summary['warn'] + $summary['fail'] }} chỉ số trên 7 trụ cột kỹ thuật</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-xl font-heading font-extrabold text-brand-green">{{ $summary['pass'] }}</p>
                    <p class="text-[11px] text-muted-foreground">Đạt</p>
                </div>
                <div class="text-center">
                    <p class="text-xl font-heading font-extrabold text-amber-600">{{ $summary['warn'] }}</p>
                    <p class="text-[11px] text-muted-foreground">Cần chú ý</p>
                </div>
                <div class="text-center">
                    <p class="text-xl font-heading font-extrabold text-destructive">{{ $summary['fail'] }}</p>
                    <p class="text-[11px] text-muted-foreground">Lỗi</p>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Pillar scorecard / quick-nav --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        @foreach ($groups as $group)
            @php
                $meta = $pillarMeta[$group['label']] ?? ['icon' => 'activity', 'id' => 'pillar-'.$loop->index];
                $gChecks = collect($group['checks']);
                $gTotal = max(1, $gChecks->count());
                $gScore = (int) round($gChecks->sum(fn (array $c) => $statusPoints($c['status'])) / $gTotal);
                $gBand = $gScore >= 90 ? 'excellent' : ($gScore >= 70 ? 'good' : 'poor');
            @endphp
            <a href="#{{ $meta['id'] }}" class="block rounded-xl border border-border bg-card p-3 hover:border-primary/50 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <x-icon :name="$meta['icon']" class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-heading font-extrabold {{ $bandText[$gBand] }}">{{ $gScore }}</span>
                </div>
                <p class="text-[11px] leading-tight text-muted-foreground">{{ $group['label'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-5 mb-6">
        @foreach ($groups as $group)
            @php
                $meta = $pillarMeta[$group['label']] ?? ['icon' => 'activity', 'id' => 'pillar-'.$loop->index];
                $sortedChecks = collect($group['checks'])->sortBy(fn (array $c) => $severityOrder($c['status']))->values();
            @endphp
            <x-card class="p-6" id="{{ $meta['id'] }}">
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h2 class="font-heading text-sm font-bold flex items-center gap-2 min-w-0">
                        <x-icon :name="$meta['icon']" class="h-4 w-4 text-muted-foreground shrink-0" />
                        <span class="truncate">{{ $group['label'] }}</span>
                    </h2>
                    @if ($group['label'] === 'Hiệu năng & Tốc độ tải trang')
                        <form method="POST" action="{{ route('admin.health.clear-cache') }}" class="shrink-0">
                            @csrf
                            <x-button type="submit" variant="outline" class="!w-auto px-3 !py-1.5 text-xs">Xoá cache</x-button>
                        </form>
                    @elseif ($group['label'] === 'Hạ tầng & Độ sẵn sàng')
                        <form method="POST" action="{{ route('admin.health.backup') }}" class="shrink-0">
                            @csrf
                            <x-button type="submit" variant="outline" class="!w-auto px-3 !py-1.5 text-xs">Sao lưu ngay</x-button>
                        </form>
                    @elseif ($group['label'] === 'Tích hợp & Theo dõi dữ liệu')
                        <form method="POST" action="{{ route('admin.health.test-email') }}" class="shrink-0">
                            @csrf
                            <x-button type="submit" variant="outline" class="!w-auto px-3 !py-1.5 text-xs">Gửi email thử nghiệm</x-button>
                        </form>
                    @endif
                </div>
                <div class="space-y-3">
                    @foreach ($sortedChecks as $check)
                        <div class="flex items-start justify-between gap-4 text-sm">
                            <div class="flex items-start gap-2.5 flex-1 min-w-0">
                                <x-icon :name="$statusIcon[$check['status']]" class="h-4 w-4 shrink-0 mt-0.5 {{ $statusColor[$check['status']] }}" />
                                <span class="text-foreground">{{ $check['label'] }}</span>
                            </div>
                            <span class="shrink-0 w-36 text-xs text-muted-foreground text-right">{{ $check['detail'] }}</span>
                        </div>
                    @endforeach

                    @if ($group['label'] === 'Hạ tầng & Độ sẵn sàng')
                        <div class="pt-3 mt-1 border-t border-border text-xs text-muted-foreground">
                            @if ($backupStatus['latestAt'])
                                Bản sao lưu gần nhất: <span class="text-foreground font-medium">{{ $backupStatus['latest'] }}</span>
                                ({{ round($backupStatus['sizeBytes'] / 1_048_576, 1) }} MB) — {{ $backupStatus['latestAt']->diffForHumans() }}, đang giữ {{ $backupStatus['count'] }} bản
                            @else
                                Chưa có bản sao lưu nào — bấm "Sao lưu ngay" ở trên.
                            @endif
                        </div>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Content decay + slow queries --}}
    <div class="grid lg:grid-cols-2 gap-5 mb-6">
        <x-card class="p-6">
            <h2 class="font-heading text-sm font-bold mb-1">Nội dung sụt giảm truy cập</h2>
            <p class="text-xs text-muted-foreground mb-4">Bài viết giảm ≥40% lượt xem 30 ngày qua so với 30 ngày trước đó — nên cập nhật lại</p>
            @if ($contentDecay->isEmpty())
                <p class="text-sm text-muted-foreground">Không có bài nào giảm mạnh 🎉</p>
            @else
                @php
                    $contentDecayRows = $contentDecay->map(fn (array $r) => [...$r, 'sub' => "{$r['prior']} → {$r['recent']} lượt"]);
                @endphp
                <x-hbar-chart
                    :data="$contentDecayRows"
                    label-key="title"
                    value-key="dropPct"
                    pct-key="dropPct"
                    sublabel-key="sub"
                    suffix="%"
                    color="var(--color-destructive)"
                />
            @endif
        </x-card>

        <x-card class="p-6">
            <h2 class="font-heading text-sm font-bold mb-1">Truy vấn CSDL chậm</h2>
            <p class="text-xs text-muted-foreground mb-4">10 truy vấn gần nhất chạy trên 500ms (không lưu giá trị tham số)</p>
            @if ($slowQueries->isEmpty())
                <p class="text-sm text-muted-foreground">Chưa ghi nhận truy vấn chậm nào 🎉</p>
            @else
                <div class="space-y-3">
                    @foreach ($slowQueries as $q)
                        <div class="text-sm border-b border-border pb-3 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-foreground font-mono text-xs truncate" title="{{ $q->sql }}">{{ $q->sql }}</p>
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[11px] font-medium {{ $q->time_ms >= 1000 ? 'bg-destructive/10 text-destructive' : 'bg-amber-500/10 text-amber-600' }}">{{ round($q->time_ms) }}ms</span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-0.5">{{ $q->path }} · {{ $q->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    {{-- IP blocking --}}
    <x-card class="p-6 mb-6">
        <div class="flex items-center justify-between mb-1">
            <h2 class="font-heading text-sm font-bold">Chặn IP</h2>
            <span class="text-xs text-muted-foreground">IP của bạn: {{ $currentIp }}</span>
        </div>
        <p class="text-xs text-muted-foreground mb-4">Chặn ở tầng ứng dụng — IP bị chặn nhận lỗi 403 ngay khi truy cập bất kỳ trang nào (public lẫn quản trị). Cẩn thận không chặn nhầm IP của chính bạn.</p>

        <form method="POST" action="{{ route('admin.health.blocked-ips.store') }}" class="flex flex-wrap items-end gap-3 mb-5">
            @csrf
            <div>
                <x-label for="ip_address">Địa chỉ IP</x-label>
                <x-input type="text" id="ip_address" name="ip_address" placeholder="203.0.113.5" required class="w-48" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-label for="reason">Lý do (không bắt buộc)</x-label>
                <x-input type="text" id="reason" name="reason" placeholder="Spam form liên hệ nhiều lần" />
            </div>
            <x-button type="submit" variant="primary" class="!w-auto px-6">Chặn</x-button>
        </form>

        @if ($blockedIps->isEmpty())
            <p class="text-sm text-muted-foreground">Chưa chặn IP nào.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-muted-foreground">
                        <th class="py-2 font-medium">IP</th>
                        <th class="py-2 font-medium">Lý do</th>
                        <th class="py-2 font-medium">Ngày chặn</th>
                        <th class="py-2 font-medium text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($blockedIps as $ip)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $ip->ip_address }}</td>
                            <td class="py-2 text-muted-foreground">{{ $ip->reason ?: '—' }}</td>
                            <td class="py-2 text-muted-foreground">{{ $ip->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('admin.health.blocked-ips.destroy', $ip) }}" onsubmit="return confirm('Gỡ chặn {{ $ip->ip_address }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-destructive hover:underline">Gỡ chặn</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-card>

    {{-- Errors + 404s --}}
    <div class="grid lg:grid-cols-2 gap-5">
        <x-card class="p-6">
            <h2 class="font-heading text-sm font-bold mb-1">Lỗi hệ thống gần đây</h2>
            <p class="text-xs text-muted-foreground mb-4">Lỗi thật xảy ra ở trang public (không tính thao tác trong quản trị)</p>
            @if ($recentErrors->isEmpty())
                <p class="text-sm text-muted-foreground">Chưa ghi nhận lỗi nào 🎉</p>
            @else
                <div class="space-y-3">
                    @foreach ($recentErrors as $error)
                        <div class="text-sm border-b border-border pb-3 last:border-0 last:pb-0">
                            <p class="text-foreground">{{ $error->message }}</p>
                            <p class="text-xs text-muted-foreground mt-0.5">
                                {{ $error->url }} · {{ $error->created_at->format('d/m/Y H:i') }}
                                @if ($error->file)
                                    <br>{{ basename($error->file) }}:{{ $error->line }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card class="p-6">
            <h2 class="font-heading text-sm font-bold mb-1">Trang không tồn tại (404)</h2>
            <p class="text-xs text-muted-foreground mb-4">7 ngày qua, sắp theo số lượt gặp — giúp tìm liên kết hỏng</p>
            @if ($topNotFound->isEmpty())
                <p class="text-sm text-muted-foreground">Chưa ghi nhận lượt 404 nào.</p>
            @else
                <div class="space-y-3">
                    @foreach ($topNotFound as $row)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate text-foreground">{{ $row->url }}</span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs text-muted-foreground">{{ $row->hits }} lượt</span>
                                <a href="{{ route('admin.redirects.index', ['source' => ltrim(parse_url($row->url, PHP_URL_PATH) ?: '', '/')]) }}" class="text-xs text-primary hover:underline">Tạo redirect →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
@endsection
