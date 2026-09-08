<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ env('SITE_FAVICON') ?: asset('favicon.svg') }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', env('SITE_TAGLINE', config('app.name')))">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    @php
        // Trang 2, 3… của một archive là URL RIÊNG. Nếu canonical trỏ hết về
        // trang 1 (hành vi của url()->current(), vốn bỏ query string) thì
        // Google được bảo rằng mọi trang phân trang đều trùng nội dung với
        // trang 1 và bỏ qua chúng — mất index toàn bộ bài từ trang 2 trở đi.
        $canonicalDefault = url()->current().((int) request()->query('page', 1) > 1 ? '?page='.(int) request()->query('page') : '');
    @endphp
    <link rel="canonical" href="@yield('canonical', $canonicalDefault)">
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} — Bài viết" href="{{ route('feed') }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', env('SITE_TAGLINE', config('app.name')))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="vi_VN">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    @hasSection('article_published_time')
        <meta property="article:published_time" content="@yield('article_published_time')">
    @endif
    @hasSection('article_modified_time')
        <meta property="article:modified_time" content="@yield('article_modified_time')">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('description', env('SITE_TAGLINE', config('app.name')))">
    @hasSection('og_image')
        <meta name="twitter:image" content="@yield('og_image')">
    @endif

    {{-- Organization + WebSite — the identity graph every page should carry,
         not just post-detail pages. Real data only: logo/social links pulled
         from the same Cài đặt fields the footer already renders, nothing
         fabricated when a field is empty. --}}
    @php
        $orgSameAs = array_values(array_filter([
            env('SITE_FACEBOOK_URL'), env('SITE_INSTAGRAM_URL'), env('SITE_TIKTOK_URL'),
            env('SITE_YOUTUBE_URL'), env('SITE_ZALO_URL'),
        ]));
    @endphp
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => config('app.name'),
                    'url' => url('/'),
                    'logo' => env('SITE_LOGO') ?: null,
                    'sameAs' => $orgSameAs ?: null,
                    'email' => env('SITE_CONTACT_EMAIL') ?: null,
                    'telephone' => env('SITE_CONTACT_PHONE') ?: null,
                ]),
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'name' => config('app.name'),
                    'url' => url('/'),
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @stack('schema')

    {!! \App\Models\Setting::get('custom_head_scripts', '') !!}

    @vite(['resources/css/app.css', 'resources/js/rum.js'])
</head>
<body class="theme-public min-h-screen bg-background text-foreground flex flex-col">
    @php
        $navLinks = [
            ['label' => 'Trang chủ', 'url' => route('home'), 'active' => request()->is('/')],
            ['label' => 'Dịch vụ', 'url' => route('services.index'), 'active' => request()->is('dich-vu*')],
            ['label' => 'Dự án', 'url' => route('projects.index'), 'active' => request()->is('du-an*')],
            ['label' => 'Sản phẩm', 'url' => route('products.index'), 'active' => request()->is('san-pham*')],
            ['label' => 'Blog', 'url' => route('blog.index'), 'active' => request()->is('blog*')],
        ];

        // "Giới thiệu" is user-generated content (a `page` post), not a fixed
        // route — only show the nav link once that page actually exists, so
        // we never ship a guaranteed-404 link on a fresh install.
        $aboutPage = \App\Models\Post::where('post_type', 'page')
            ->where('slug', 'gioi-thieu')
            ->where('status', 'published')
            ->first();

        if ($aboutPage) {
            $navLinks[] = ['label' => 'Giới thiệu', 'url' => route('post.show', $aboutPage->slug), 'active' => request()->is('gioi-thieu')];
        }

        // Cho plugin thêm/bớt mục menu mà không phải sửa theme lõi. Nếu ghi
        // cứng link ở đây thì mọi website cài CMS này cũng mọc thêm mục đó —
        // trong khi nó chỉ đúng với một site cụ thể.
        $navLinks = apply_filters('mxd_nav_links', $navLinks);

        $socialLinks = collect([
            'facebook' => env('SITE_FACEBOOK_URL'),
            'message-circle' => env('SITE_ZALO_URL'),
            'instagram' => env('SITE_INSTAGRAM_URL'),
            'tiktok' => env('SITE_TIKTOK_URL'),
            'youtube' => env('SITE_YOUTUBE_URL'),
        ])->filter();

        $siteLogo = env('SITE_LOGO');
    @endphp

    <header class="sticky top-0 z-20 border-b border-border bg-background/80 backdrop-blur">
        <div class="mx-auto max-w-[1440px] px-4 py-3.5 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0">
                @if ($siteLogo)
                    <img src="{{ $siteLogo }}" alt="{{ config('app.name') }}" class="h-8 w-auto max-w-[160px] object-contain">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-green/10 text-lg">🌱</span>
                    <span class="font-heading text-base font-extrabold">{{ config('app.name') }}</span>
                @endif
            </a>

            <nav class="hidden md:flex items-center gap-8">
                @foreach ($navLinks as $link)
                    <a href="{{ $link['url'] }}" class="text-sm transition-colors border-b pb-0.5 {{ $link['active'] ? 'text-foreground border-primary' : 'text-muted-foreground border-transparent hover:text-foreground' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden md:flex items-center gap-5">
                <a href="{{ route('search') }}" aria-label="Tìm kiếm"
                   class="text-muted-foreground hover:text-foreground transition-colors {{ request()->routeIs('search') ? 'text-foreground' : '' }}">
                    <x-icon name="search" class="h-[18px] w-[18px]" />
                </a>
                <a href="{{ route('contact.show') }}" class="group inline-flex items-center gap-2 text-sm font-semibold">
                    <span class="border-b-2 border-primary pb-0.5">Liên hệ</span>
                    <span class="text-primary transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
                </a>
            </div>

            <button id="public-nav-open" class="md:hidden text-foreground">
                <x-icon name="menu" />
            </button>
        </div>

        <div id="public-nav-mobile" class="hidden md:hidden border-t border-border px-4 pb-4">
            @foreach ($navLinks as $link)
                <a href="{{ $link['url'] }}" class="block border-b border-border py-3.5 font-heading text-lg {{ $link['active'] ? 'text-primary' : '' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <a href="{{ route('search') }}" class="block border-b border-border py-3.5 font-heading text-lg">Tìm kiếm</a>
            <a href="{{ route('contact.show') }}" class="block py-3.5 font-heading text-lg text-primary">Liên hệ →</a>
        </div>
    </header>

    <script>
        document.getElementById('public-nav-open')?.addEventListener('click', () => {
            document.getElementById('public-nav-mobile')?.classList.toggle('hidden');
        });
    </script>

    <main class="flex-1 mx-auto max-w-[1440px] w-full px-4 sm:px-8 lg:px-12">
        @yield('content')
    </main>

    <footer class="border-t border-border">
        <div class="mx-auto max-w-[1440px] px-4 py-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center gap-2">
                    @if ($siteLogo)
                        <img src="{{ $siteLogo }}" alt="{{ config('app.name') }}" class="h-6 w-auto max-w-[140px] object-contain">
                    @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-green/10 text-sm">🌱</span>
                        <span class="font-heading text-sm font-bold">{{ config('app.name') }}</span>
                    @endif
                </div>
                @if (env('SITE_TAGLINE'))
                    <p class="mt-3 text-sm text-muted-foreground leading-relaxed">{{ env('SITE_TAGLINE') }}</p>
                @endif
                @if ($socialLinks->isNotEmpty())
                    <div class="mt-4 flex items-center gap-3">
                        @foreach ($socialLinks as $icon => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground hover:text-primary hover:border-primary transition-colors">
                                <x-icon :name="$icon" class="h-4 w-4" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Liên kết</p>
                <ul class="space-y-2 text-sm">
                    @foreach ($navLinks as $link)
                        <li><a href="{{ $link['url'] }}" class="text-muted-foreground hover:text-foreground">{{ $link['label'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('search') }}" class="text-muted-foreground hover:text-foreground">Tìm kiếm</a></li>
                </ul>
            </div>

            @php
                // Chỉ liệt kê danh mục THẬT SỰ có bài đã xuất bản — footer là
                // nơi crawler đi qua ở mọi trang, một link tới archive rỗng là
                // ngõ cụt cho cả người đọc lẫn bot.
                $footerTerms = \App\Models\Term::whereIn('taxonomy', ['category', 'product_category'])
                    ->whereHas('posts', fn ($q) => $q->where('status', 'published'))
                    ->orderBy('name')
                    ->limit(8)
                    ->get();
            @endphp
            @if ($footerTerms->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Chủ đề</p>
                    <ul class="space-y-2 text-sm">
                        @foreach ($footerTerms as $term)
                            @if ($term->url())
                                <li><a href="{{ $term->url() }}" class="text-muted-foreground hover:text-foreground">{{ $term->name }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Liên hệ</p>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    @if (env('SITE_ADDRESS'))
                        <li>{{ env('SITE_ADDRESS') }}</li>
                    @endif
                    @if (env('SITE_CONTACT_EMAIL'))
                        <li><a href="mailto:{{ env('SITE_CONTACT_EMAIL') }}" class="hover:text-foreground">{{ env('SITE_CONTACT_EMAIL') }}</a></li>
                    @endif
                    @if (env('SITE_CONTACT_PHONE'))
                        <li><a href="tel:{{ env('SITE_CONTACT_PHONE') }}" class="hover:text-foreground">{{ env('SITE_CONTACT_PHONE') }}</a></li>
                    @endif
                    <li><a href="{{ route('contact.show') }}" class="hover:text-foreground">Gửi liên hệ →</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-border">
            <div class="mx-auto max-w-[1440px] px-4 py-4">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                    <p class="text-xs text-muted-foreground">© {{ now()->year }} {{ config('app.name') }}. Đã đăng ký bản quyền.</p>
                    <p class="flex items-center gap-4 text-xs text-muted-foreground">
                        <a href="{{ route('feed') }}" class="hover:text-foreground">RSS</a>
                        <a href="{{ route('sitemap') }}" class="hover:text-foreground">Sitemap</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Hiện dần khi cuộn tới — thuần JS, không thư viện. Đặt ở layout
        // (không phải riêng home) để MỌI trang public đều dùng được cùng
        // một hiệu ứng; trước đây script nằm trong home.blade.php nên bất
        // kỳ trang nào khác gắn class .reveal sẽ ẩn vĩnh viễn.
        // Nếu trình duyệt không hỗ trợ IntersectionObserver thì hiện hết
        // ngay lập tức — không bao giờ để nội dung thật bị ẩn vì 1 script.
        (function () {
            const targets = document.querySelectorAll('.reveal, .reveal-stagger, .reveal-image');

            if (!('IntersectionObserver' in window)) {
                targets.forEach((el) => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

            targets.forEach((el) => observer.observe(el));
        })();
    </script>

    @stack('scripts')

    {!! \App\Models\Setting::get('custom_footer_scripts', '') !!}
</body>
</html>
