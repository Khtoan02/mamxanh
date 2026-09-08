<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @stack('head')
    {{-- Applied before paint so a collapsed sidebar doesn't flash open on
         hard page loads — Turbo navigations never re-run this (only <body>
         is swapped), so the class just persists on <html> across visits. --}}
    <script>
        if (localStorage.getItem('mxd_sidebar_collapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
</head>
<body class="min-h-screen bg-background text-foreground">
    <div class="flex min-h-screen">
        {{-- Overlay (mobile) --}}
        <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-border bg-card transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-border px-5">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" title="Xem trang chủ Website" class="flex min-w-0 items-center">
                    @if (env('SITE_LOGO'))
                        <img src="{{ env('SITE_LOGO') }}" alt="{{ config('app.name') }}" class="sidebar-label h-7 w-auto max-w-[170px] object-contain">
                    @else
                        <span class="sidebar-label truncate font-heading text-sm font-extrabold">{{ config('app.name') }}</span>
                    @endif
                </a>
                <button id="sidebar-collapse-toggle" title="Thu gọn menu" class="hidden shrink-0 text-muted-foreground transition-colors hover:text-foreground lg:flex">
                    <x-icon name="chevron-left" class="h-4 w-4 transition-transform duration-150" />
                </button>
                <button id="sidebar-close" class="text-muted-foreground lg:hidden">
                    <x-icon name="x" />
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-3">
                <x-nav-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" title="Dashboard">
                    <x-icon name="home" />
                    <span class="sidebar-label truncate">Dashboard</span>
                </x-nav-item>

                {{-- Nhóm 1 — Nội dung: Thư viện ảnh rồi tới từng loại nội
                     dung đã đăng ký, thứ tự ưu tiên Bài viết/Sản phẩm trước
                     (2 loại có danh mục riêng nên hiện dạng nhóm cha/con —
                     tính động từ PostTypeRegistry/TaxonomyRegistry nên tự
                     nhận loại nội dung mới nếu sau này có đăng ký thêm),
                     Trang/Dịch vụ/Dự án sau (không có taxonomy nên hiện
                     link phẳng). --}}
                <x-nav-section-label>Nội dung</x-nav-section-label>
                @can('edit_posts')
                    <x-nav-item :href="route('admin.media.index')" :active="request()->routeIs('admin.media.*')" title="Thư viện ảnh">
                        <x-icon name="image" />
                        <span class="sidebar-label truncate">Thư viện ảnh</span>
                    </x-nav-item>
                @endcan
                @php
                    $sidebarTypeIcons = ['post' => 'file-text', 'page' => 'book-open', 'service' => 'target', 'project' => 'briefcase', 'product' => 'tag'];
                    $sidebarTypeOrder = ['post', 'product', 'page', 'service', 'project'];
                    $allPostTypes = get_post_types();
                    $orderedPostTypes = collect($sidebarTypeOrder)
                        ->filter(fn ($slug) => isset($allPostTypes[$slug]))
                        ->concat(array_diff(array_keys($allPostTypes), $sidebarTypeOrder))
                        ->mapWithKeys(fn ($slug) => [$slug => $allPostTypes[$slug]]);
                    $currentTaxonomySlug = request()->route('taxonomy');
                @endphp
                @foreach ($orderedPostTypes as $ptSlug => $pt)
                    @php
                        $ptTaxonomies = get_taxonomies_for_post_type($ptSlug);
                        $ptIcon = $sidebarTypeIcons[$ptSlug] ?? 'file-text';
                        $ptActive = request()->routeIs('admin.content.*') && request()->query('type', 'post') === $ptSlug;
                        $ptTaxonomyActive = $currentTaxonomySlug && array_key_exists($currentTaxonomySlug, $ptTaxonomies);
                    @endphp
                    @if (empty($ptTaxonomies))
                        <x-nav-item :href="route('admin.content.index', ['type' => $ptSlug])" :active="$ptActive" :title="$pt['label']">
                            <x-icon :name="$ptIcon" />
                            <span class="sidebar-label truncate">{{ $pt['label'] }}</span>
                        </x-nav-item>
                    @else
                        <x-nav-group :label="$pt['label']" :icon="$ptIcon" :active="$ptActive || $ptTaxonomyActive">
                            <x-nav-subitem :href="route('admin.content.index', ['type' => $ptSlug])" :active="$ptActive">
                                Tất cả {{ mb_strtolower($pt['label']) }}
                            </x-nav-subitem>
                            @can('manage_options')
                                @foreach ($ptTaxonomies as $taxSlug => $tax)
                                    <x-nav-subitem :href="route('admin.taxonomies.index', ['taxonomy' => $taxSlug])" :active="$currentTaxonomySlug === $taxSlug">
                                        {{ $tax['label'] }}
                                    </x-nav-subitem>
                                @endforeach
                            @endcan
                        </x-nav-group>
                    @endif
                @endforeach

                {{-- Nhóm 2 — Marketing & theo dõi: công cụ đo lường/tăng
                     trưởng, dùng thường xuyên nên để phẳng, không ẩn sau
                     accordion. Sức khỏe Website xếp ở đây (không phải
                     nhóm Hệ thống) vì bản chất là theo dõi/giám sát liên
                     tục, giống Phân tích, chứ không phải cấu hình 1 lần. --}}
                @can('manage_options')
                    <x-nav-section-label>Marketing &amp; Theo dõi</x-nav-section-label>
                    <x-nav-item :href="route('admin.analytics')" :active="request()->routeIs('admin.analytics')" title="Phân tích">
                        <x-icon name="eye" />
                        <span class="sidebar-label truncate">Phân tích</span>
                    </x-nav-item>
                    <x-nav-item :href="route('admin.marketing')" :active="request()->routeIs('admin.marketing')" title="Marketing">
                        <x-icon name="megaphone" />
                        <span class="sidebar-label truncate">Marketing</span>
                    </x-nav-item>
                    <x-nav-item :href="route('admin.contacts.index')" :active="request()->routeIs('admin.contacts.*')" title="CRM">
                        <x-icon name="mail" />
                        <span class="sidebar-label truncate">CRM</span>
                        @php
                            $unreadContacts = \App\Models\ContactMessage::where('read', false)->count();
                        @endphp
                        @if ($unreadContacts > 0)
                            <span class="sidebar-label ml-auto rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground">{{ $unreadContacts }}</span>
                        @endif
                    </x-nav-item>
                    <x-nav-item :href="route('admin.health.index')" :active="request()->routeIs('admin.health.*')" title="Sức khỏe Website">
                        <x-icon name="activity" />
                        <span class="sidebar-label truncate">Sức khỏe Website</span>
                    </x-nav-item>
                @endcan

                {{-- Nhóm 3 — Hệ thống: cấu hình, ít khi đụng tới, cũng để
                     phẳng theo đúng yêu cầu (không gộp vào 1 tab cha). --}}
                @if (auth()->user()->can('manage_users') || auth()->user()->can('manage_options'))
                    <x-nav-section-label>Hệ thống</x-nav-section-label>
                    @can('manage_users')
                        <x-nav-item :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" title="Người dùng">
                            <x-icon name="users" />
                            <span class="sidebar-label truncate">Người dùng</span>
                        </x-nav-item>
                    @endcan
                    @can('manage_options')
                        <x-nav-item :href="route('admin.seo.titles')" :active="request()->routeIs('admin.seo.*')" title="SEO">
                            <x-icon name="search" />
                            <span class="sidebar-label truncate">SEO</span>
                        </x-nav-item>
                        <x-nav-item :href="route('admin.redirects.index')" :active="request()->routeIs('admin.redirects.*')" title="Chuyển hướng">
                            <x-icon name="repeat" />
                            <span class="sidebar-label truncate">Chuyển hướng</span>
                        </x-nav-item>
                        <x-nav-item :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" title="Vai trò">
                            <x-icon name="shield" />
                            <span class="sidebar-label truncate">Vai trò</span>
                        </x-nav-item>
                        <x-nav-item :href="route('admin.themes.index')" :active="request()->routeIs('admin.themes.*')" title="Giao diện">
                            <x-icon name="palette" />
                            <span class="sidebar-label truncate">Giao diện</span>
                        </x-nav-item>
                        <x-nav-item :href="route('admin.settings.general')" :active="request()->routeIs('admin.settings.*')" title="Cài đặt">
                            <x-icon name="settings" />
                            <span class="sidebar-label truncate">Cài đặt</span>
                        </x-nav-item>
                    @endcan
                @endif
            </nav>

            {{-- Sidebar footer --}}
            <div class="shrink-0 space-y-1 border-t border-border p-3">
                <x-nav-item :href="route('admin.help')" :active="request()->routeIs('admin.help')" title="Trợ giúp và Thông tin">
                    <x-icon name="help-circle" />
                    <span class="sidebar-label truncate">Trợ giúp và Thông tin</span>
                </x-nav-item>

                <div class="user-block flex items-center gap-2.5 rounded-lg px-3 py-2">
                    <x-avatar :name="auth()->user()->name" class="h-8 w-8 text-xs" />
                    <div class="sidebar-label min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-foreground">{{ auth()->user()->name }}</p>
                        <x-badge variant="default" class="mt-0.5">{{ auth()->user()->assignedRole?->name ?? auth()->user()->role }}</x-badge>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Đăng xuất" class="text-muted-foreground transition-colors hover:text-destructive">
                            <x-icon name="log-out" class="h-4 w-4" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 items-center gap-3 border-b border-border bg-card px-4 lg:px-6">
                <button id="sidebar-open" class="text-muted-foreground lg:hidden">
                    <x-icon name="menu" />
                </button>
                <h1 class="font-heading text-sm font-bold">@yield('title', 'Dashboard')</h1>
            </header>

            <main id="admin-main" class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
