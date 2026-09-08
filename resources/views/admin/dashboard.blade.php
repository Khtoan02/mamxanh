@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $statusColor = ['new' => 'default', 'contacted' => 'secondary', 'won' => 'green', 'lost' => 'secondary'];
@endphp

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="font-heading text-lg font-bold">Xin chào, {{ $user->name }} 👋</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Quyền hiện tại: <span class="font-semibold text-foreground">{{ $user->assignedRole?->name ?? $user->role }}</span>
                · <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-green animate-pulse"></span>
                    {{ $liveVisitors }} đang online
                </span>
            </p>
        </div>
        <a href="{{ route('admin.analytics') }}" class="hidden sm:inline text-sm text-primary hover:underline">Xem phân tích chi tiết →</a>
    </div>

    {{-- KPI overview --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Lượt xem (7 ngày)</p>
                <x-icon name="eye" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($weekViews) }}</p>
            <div class="mt-2"><x-trend-pill :delta="$weekViewsDelta" /></div>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Người xem duy nhất</p>
                <x-icon name="users-round" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($weekUniqueVisitors) }}</p>
            <div class="mt-2"><x-trend-pill :delta="$weekUniqueVisitorsDelta" /></div>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Liên hệ mới</p>
                <x-icon name="mail-plus" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ number_format($weekLeads) }}</p>
            <div class="mt-2"><x-trend-pill :delta="$weekLeadsDelta" /></div>
        </x-card>
        <x-card class="p-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">Tỉ lệ chuyển đổi</p>
                <x-icon name="target" class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-2xl font-heading font-extrabold">{{ $conversionRate !== null ? number_format($conversionRate, 1).'%' : '—' }}</p>
            <p class="mt-2 text-[11px] text-muted-foreground">Liên hệ / lượt xem</p>
        </x-card>
    </div>

    {{-- Trend chart --}}
    <x-card class="p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-semibold">Lượt xem 14 ngày gần nhất</p>
            <p class="text-xs text-muted-foreground">Cao nhất: {{ number_format($chart['maxViews']) }} lượt/ngày</p>
        </div>
        <x-area-chart :chart="$chart" />
    </x-card>

    {{-- Traffic breakdowns --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Trang xem nhiều nhất (7 ngày)</p>
            <x-hbar-chart :data="$topPages" label-key="path" value-key="views" color="var(--color-primary)" />
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Nguồn giới thiệu (7 ngày)</p>
            @if ($topReferrers->isEmpty())
                <p class="text-sm text-muted-foreground">Chưa có lượt truy cập từ nguồn ngoài.</p>
            @else
                <x-hbar-chart :data="$topReferrers" label-key="referrer_host" value-key="views" color="var(--color-primary)" />
            @endif
        </x-card>

        <x-card class="p-5">
            <p class="text-sm font-semibold mb-4">Phân bổ thiết bị</p>
            <x-donut-chart :data="$devices" value-key="views" :colors="['var(--color-primary)', 'var(--color-brand-green)', '#f59e0b']" size="112" />
        </x-card>
    </div>

    {{-- Marketing + CRM + Health --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <x-card class="p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold">Hiệu suất nội dung (30 ngày)</p>
                <a href="{{ route('admin.marketing') }}" class="text-xs text-primary hover:underline">Marketing →</a>
            </div>
            <x-donut-chart :data="$contentPerformance->take(5)" value-key="views" size="112" />
        </x-card>

        <x-card class="p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold">Nguồn dẫn lead</p>
                <a href="{{ route('admin.marketing') }}" class="text-xs text-primary hover:underline">Marketing →</a>
            </div>
            <x-donut-chart :data="$leadSources" label-key="source" value-key="count" size="112" :colors="['var(--color-brand-green)', 'var(--color-primary)', '#f59e0b', '#0ea5e9', '#a855f7', '#64748b']" />
        </x-card>

        <x-card class="p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold">CRM — Trạng thái lead</p>
                <a href="{{ route('admin.contacts.index') }}" class="text-xs text-primary hover:underline">CRM →</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach (\App\Models\ContactMessage::STATUS_LABELS as $key => $label)
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xl font-heading font-extrabold">{{ $crmStatusCounts[$key] ?? 0 }}</p>
                        <p class="text-[11px] text-muted-foreground">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    {{-- System health summary --}}
    <x-card class="p-5 mb-6">
        <div class="flex items-center justify-between">
            <p class="text-sm font-semibold">Sức khoẻ hệ thống</p>
            <a href="{{ route('admin.health.index') }}" class="text-xs text-primary hover:underline">Xem chi tiết →</a>
        </div>
        <div class="mt-4 flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-brand-green"></span>
                <span class="text-sm"><span class="font-semibold">{{ $healthSummary['pass'] }}</span> đạt</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                <span class="text-sm"><span class="font-semibold">{{ $healthSummary['warn'] }}</span> cần chú ý</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-destructive"></span>
                <span class="text-sm"><span class="font-semibold">{{ $healthSummary['fail'] }}</span> lỗi</span>
            </div>
        </div>
    </x-card>

    {{-- Content overview --}}
    <p class="text-sm font-semibold mb-3">Tổng quan nội dung</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @foreach ($counts as $slug => $data)
            <x-card class="p-4">
                <p class="text-2xl font-heading font-extrabold">{{ $data['total'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $data['label'] }}</p>
                @if ($data['total'] > $data['published'])
                    <p class="mt-0.5 text-[11px] text-muted-foreground">{{ $data['published'] }} đã đăng</p>
                @endif
            </x-card>
        @endforeach
        <x-card class="p-4">
            <p class="text-2xl font-heading font-extrabold">{{ $mediaCount }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Media trong thư viện</p>
        </x-card>
        <x-card class="p-4">
            <p class="text-2xl font-heading font-extrabold">{{ $userCount }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Người dùng</p>
        </x-card>
    </div>

    @can('edit_posts')
        <div class="flex flex-wrap gap-3 mb-8">
            <a href="{{ route('admin.content.create', ['type' => 'post']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold bg-primary text-primary-foreground hover:opacity-90">+ Viết bài mới</a>
            <a href="{{ route('admin.content.create', ['type' => 'service']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold border border-border hover:bg-accent">+ Thêm Dịch vụ</a>
            <a href="{{ route('admin.content.create', ['type' => 'product']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold border border-border hover:bg-accent">+ Thêm Sản phẩm</a>
        </div>
    @endcan

    <div class="grid lg:grid-cols-2 gap-4">
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-heading text-sm font-bold">Nội dung gần đây</h3>
            </div>
            <x-card class="overflow-hidden">
                @if ($recentPosts->isEmpty())
                    <div class="p-8 text-center text-sm text-muted-foreground">Chưa có nội dung nào.</div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Tiêu đề</th>
                                <th class="px-4 py-3 font-medium">Trạng thái</th>
                                <th class="px-4 py-3 font-medium text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($recentPosts as $post)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium truncate max-w-[220px]">{{ $post->title }}</p>
                                        <p class="text-xs text-muted-foreground">{{ get_post_type($post->post_type)['label'] ?? $post->post_type }} · {{ $post->updated_at->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-badge :variant="$post->status === 'published' ? 'green' : 'secondary'">
                                            {{ $post->status === 'published' ? 'Đã đăng' : 'Nháp' }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @can('edit_posts')
                                            <a href="{{ route('admin.content.edit', $post) }}" class="text-primary hover:underline text-sm">Sửa</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-card>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-heading text-sm font-bold">Liên hệ gần đây</h3>
                <a href="{{ route('admin.contacts.index') }}" class="text-xs text-primary hover:underline">Xem CRM →</a>
            </div>
            <x-card class="overflow-hidden">
                @if ($recentContacts->isEmpty())
                    <div class="p-8 text-center text-sm text-muted-foreground">Chưa có liên hệ nào.</div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Khách hàng</th>
                                <th class="px-4 py-3 font-medium">Ngày</th>
                                <th class="px-4 py-3 font-medium text-right">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($recentContacts as $contact)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ $contact->name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ $contact->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $contact->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-badge :variant="$statusColor[$contact->status] ?? 'default'">
                                            {{ $contact->statusLabel() }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-card>
        </div>
    </div>
@endsection
