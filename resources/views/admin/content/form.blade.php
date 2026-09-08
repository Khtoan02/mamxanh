@extends('layouts.admin')

@section('title', $post->exists ? 'Sửa ' . $postType['label_singular'] : 'Thêm ' . $postType['label_singular'])

@push('head')
    {{-- TinyMCE builds its own live editor DOM outside normal Blade rendering
         (iframe, toolbars) — Turbo Drive's instant "preview from cache" on
         revisiting this page would show a stale/frozen snapshot of that
         instead of a clean re-init. Skip caching this page entirely. --}}
    <meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif

    {{-- Media picker modal — shared between the TinyMCE toolbar button and the
         Featured Image field below. Opens via openMediaPicker(onSelect). --}}
    @can('edit_posts')
        <x-media-picker-modal />
    @endcan

    <form id="content-form" method="POST" action="{{ $post->exists ? route('admin.content.update', $post) : route('admin.content.store') }}">
        @csrf
        @if ($post->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="post_type" value="{{ $postType['slug'] }}">

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Cột chính: nội dung thực sự tác giả gõ ra --}}
            <div class="lg:col-span-2 space-y-5">
                <x-card class="p-6 space-y-4">
                    <div>
                        <x-label for="title">Tiêu đề</x-label>
                        <x-input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required autofocus />
                    </div>

                    <div>
                        <x-label for="slug">Slug (để trống để tự tạo từ tiêu đề)</x-label>
                        <x-input type="text" id="slug" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="vi-du-tieu-de" />
                    </div>

                    @if (in_array('editor', $postType['supports'], true))
                        <div>
                            <x-label for="content">Nội dung</x-label>
                            <textarea id="content" name="content" rows="10" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('content', $post->content) }}</textarea>
                            <p class="mt-1.5 text-xs text-muted-foreground">Kéo góc ảnh trong bài để đổi kích thước — tỉ lệ được giữ nguyên tự động.</p>
                        </div>

                        @can('edit_posts')
                            @push('scripts')
                                <script>
                                    (function () {
                                        // Under Turbo Drive, re-inserted <script src> tags don't block
                                        // the way a native page-load parse does — a separate init
                                        // script right after it can run before tinymce.min.js has
                                        // actually finished loading. Load-then-init in one place
                                        // instead of relying on document order between two tags.
                                        function boot() {
                                        const dark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                                        tinymce.init({
                                            selector: '#content',
                                            license_key: 'gpl',
                                            height: 420,
                                            menubar: false,
                                            branding: false,
                                            skin: dark ? 'oxide-dark' : 'oxide',
                                            content_css: dark ? 'dark' : 'default',
                                            // "blockquote" is a built-in toolbar command, not a real
                                            // plugin — listing it here just 404s on every load.
                                            plugins: 'lists link image table code',
                                            toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist blockquote | link mxdimage table | code | removeformat',
                                            automatic_uploads: true,
                                            images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                                                const formData = new FormData();
                                                formData.append('file', blobInfo.blob(), blobInfo.filename());

                                                fetch(@json(route('admin.media.store')), {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                                                        'Accept': 'application/json',
                                                    },
                                                    body: formData,
                                                })
                                                    .then((res) => res.ok ? res.json() : Promise.reject())
                                                    .then((json) => resolve(json.location))
                                                    .catch(() => reject('Tải ảnh lên thất bại.'));
                                            }),
                                            setup: (editor) => {
                                                editor.ui.registry.addButton('mxdimage', {
                                                    icon: 'gallery',
                                                    tooltip: 'Chèn ảnh từ thư viện',
                                                    onAction: () => openMediaPicker((item) => {
                                                        editor.insertContent(`<img src="${item.url}" alt="${item.name || ''}" style="max-width:100%;height:auto;">`);
                                                    }),
                                                });

                                                editor.on('input undo redo SetContent', () => window.updateSeoScore && window.updateSeoScore());
                                            },
                                        });

                                        // Turbo Drive snapshots the page before navigating away (for
                                        // instant back/forward previews) — without this, that snapshot
                                        // would capture TinyMCE's live editor DOM instead of a plain
                                        // textarea, which breaks re-initialization on the next visit.
                                        document.addEventListener('turbo:before-cache', function cleanup() {
                                            if (window.tinymce) tinymce.remove('#content');
                                        }, { once: true });
                                        }

                                        if (window.tinymce) {
                                            boot();
                                        } else {
                                            const script = document.createElement('script');
                                            script.src = @json(asset('vendor/tinymce/tinymce.min.js'));
                                            script.referrerPolicy = 'origin';
                                            script.onload = boot;
                                            document.head.appendChild(script);
                                        }
                                    })();
                                </script>
                            @endpush
                        @endcan
                    @endif

                    @if (in_array('excerpt', $postType['supports'], true))
                        <div>
                            <x-label for="excerpt">Mô tả ngắn</x-label>
                            <textarea id="excerpt" name="excerpt" rows="3" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('excerpt', $post->excerpt) }}</textarea>
                        </div>
                    @endif
                </x-card>

                @if (! empty($customFields))
                    <x-card class="p-6 space-y-4">
                        <h2 class="font-heading text-sm font-bold">Thông tin thêm</h2>
                        @foreach ($customFields as $field => $label)
                            <div>
                                <x-label for="{{ $field }}">{{ $label }}</x-label>
                                <x-input type="text" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $post->exists ? $post->getMeta($field) : null) }}" />
                            </div>
                        @endforeach
                    </x-card>
                @endif

                <x-card class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-heading text-sm font-bold">Câu hỏi thường gặp (FAQ)</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Chỉ hiện thị khi có nội dung thật — hiển thị trên trang và tạo dữ liệu có cấu trúc (schema) từ đúng nội dung này.</p>
                        </div>
                        <button type="button" id="faq-add" class="shrink-0 rounded-lg border border-border px-3 py-1.5 text-sm font-medium hover:bg-accent">+ Thêm câu hỏi</button>
                    </div>

                    <div id="faq-rows" class="space-y-3"></div>

                    <input type="hidden" id="faq_items" name="faq_items">
                </x-card>

                @php
                    $faqItemsInitial = old('faq_items');
                    if ($faqItemsInitial === null && $post->exists) {
                        $faqItemsInitial = $post->getMeta('faq_items');
                    }
                @endphp

                @push('scripts')
                    <script>
                        (function () {
                            const rowsEl = document.getElementById('faq-rows');
                            const hiddenEl = document.getElementById('faq_items');
                            const addBtn = document.getElementById('faq-add');
                            const initial = @json($faqItemsInitial ? json_decode($faqItemsInitial, true) : []);

                            function addRow(q = '', a = '') {
                                const row = document.createElement('div');
                                row.className = 'rounded-lg border border-border p-3 space-y-2';
                                row.innerHTML = `
                                    <div class="flex items-start gap-2">
                                        <input type="text" class="faq-q flex-1 rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Câu hỏi">
                                        <button type="button" class="faq-remove shrink-0 text-sm text-destructive hover:underline px-1 py-2">Xoá</button>
                                    </div>
                                    <textarea class="faq-a w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring" rows="2" placeholder="Câu trả lời"></textarea>
                                `;
                                row.querySelector('.faq-q').value = q;
                                row.querySelector('.faq-a').value = a;
                                row.querySelector('.faq-remove').addEventListener('click', () => row.remove());
                                rowsEl.appendChild(row);
                            }

                            addBtn.addEventListener('click', () => addRow());

                            (initial || []).forEach((item) => addRow(item.q || '', item.a || ''));

                            document.getElementById('content-form').addEventListener('submit', () => {
                                const items = Array.from(rowsEl.querySelectorAll(':scope > div')).map((row) => ({
                                    q: row.querySelector('.faq-q').value.trim(),
                                    a: row.querySelector('.faq-a').value.trim(),
                                })).filter((item) => item.q && item.a);
                                // Disabled (not just empty) when there's nothing to
                                // submit — an empty-string value would still reach
                                // the server and fail Laravel's `json` validation
                                // rule, since `nullable` only skips a truly-absent
                                // field, not an empty string.
                                hiddenEl.disabled = items.length === 0;
                                hiddenEl.value = items.length ? JSON.stringify(items) : '';
                            });
                        })();
                    </script>
                @endpush
            </div>

            {{-- Cột phụ: thao tác nhanh + metadata — luôn dính theo khi cuộn
                 trang chính dài, đúng thứ tự ưu tiên thao tác (xuất bản trước,
                 rồi mới tới metadata). --}}
            <div class="lg:col-span-1 space-y-5 lg:sticky lg:top-6 lg:self-start">
                <x-card class="p-6 space-y-4">
                    <div>
                        <x-label for="status">Trạng thái</x-label>
                        @can('publish_posts')
                            <select id="status" name="status" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="draft" @selected(old('status', $post->status) === 'draft')>Nháp</option>
                                <option value="published" @selected(old('status', $post->status) === 'published')>Đã đăng</option>
                            </select>
                        @else
                            <input type="hidden" name="status" value="draft">
                            <p class="text-sm text-muted-foreground">Lưu dạng nháp — tài khoản của bạn chưa có quyền đăng bài trực tiếp.</p>
                        @endcan
                    </div>

                    <div class="flex gap-3">
                        <x-button type="submit" variant="primary" class="!w-auto px-6">{{ $post->exists ? 'Cập nhật' : 'Lưu' }}</x-button>
                        <a href="{{ route('admin.content.index', ['type' => $postType['slug']]) }}" class="inline-flex items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-accent">Huỷ</a>
                    </div>
                </x-card>

                @if (in_array('featured_image', $postType['supports'], true))
                    <x-card class="p-6 space-y-4">
                        <x-media-picker-field name="featured_image" label="Ảnh đại diện" :value="$post->featured_image" />
                    </x-card>
                @endif

                @php
                    // A hierarchical taxonomy (category-style) only renders
                    // once it has terms to pick from; a non-hierarchical one
                    // (tag-style) is a free-text field and always renders.
                    // Skip the whole card when nothing inside it actually
                    // will — otherwise an empty product_category with no
                    // terms yet leaves a blank white box in the sidebar.
                    $hasVisibleTaxonomy = collect($taxonomies)->contains(
                        fn ($taxonomy, $slug) => ! $taxonomy['hierarchical'] || $terms->get($slug, collect())->isNotEmpty()
                    );
                @endphp
                @if ($hasVisibleTaxonomy)
                    <x-card class="p-6 space-y-4">
                        @foreach ($taxonomies as $slug => $taxonomy)
                            @php
                                $taxonomyTerms = $terms->get($slug, collect());
                                $currentTermId = collect($selectedTermIds)->intersect($taxonomyTerms->pluck('id'))->first();
                                $currentTermNames = $post->exists ? $post->terms->where('taxonomy', $slug)->pluck('name')->implode(', ') : '';
                            @endphp

                            @if ($taxonomy['hierarchical'])
                                @if ($taxonomyTerms->isNotEmpty())
                                    <div>
                                        <x-label for="taxonomy_{{ $slug }}">{{ $taxonomy['label'] }}</x-label>
                                        <select id="taxonomy_{{ $slug }}" name="taxonomy_{{ $slug }}" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                            <option value="">— Không chọn —</option>
                                            @foreach ($taxonomyTerms as $term)
                                                <option value="{{ $term->id }}" @selected(old("taxonomy_{$slug}", $currentTermId) == $term->id)>{{ $term->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            @else
                                <div>
                                    <x-label for="taxonomy_{{ $slug }}">{{ $taxonomy['label'] }} (cách nhau bởi dấu phẩy)</x-label>
                                    <x-input type="text" id="taxonomy_{{ $slug }}" name="taxonomy_{{ $slug }}" value="{{ old('taxonomy_'.$slug, $currentTermNames) }}" placeholder="seo, laravel, tin-tuc" />
                                </div>
                            @endif
                        @endforeach
                    </x-card>
                @endif

                <x-card class="p-6 space-y-4">
                    <h2 class="font-heading text-sm font-bold">SEO</h2>

                    <div class="flex gap-1 rounded-lg border border-border bg-muted p-1 w-fit" data-tabs="seo">
                        <button type="button" data-tab="general" class="tab-btn rounded-md px-3 py-1 text-xs font-medium bg-card shadow-sm text-foreground">Chung</button>
                        <button type="button" data-tab="advanced" class="tab-btn rounded-md px-3 py-1 text-xs font-medium text-muted-foreground hover:text-foreground">Nâng cao</button>
                    </div>

                    <div data-tab-panel-group="seo">
                        <div data-tab-panel="general" class="space-y-4">
                            <div>
                                <x-label for="meta_title">Tiêu đề SEO (để trống dùng Tiêu đề)</x-label>
                                <x-input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $post->exists ? $post->getMeta('meta_title') : null) }}" maxlength="255" />
                            </div>

                            <div>
                                <x-label for="meta_description">Mô tả SEO (để trống dùng Mô tả ngắn)</x-label>
                                <textarea id="meta_description" name="meta_description" rows="2" maxlength="255" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('meta_description', $post->exists ? $post->getMeta('meta_description') : null) }}</textarea>
                                <p class="mt-1 text-xs text-muted-foreground"><span id="meta-desc-count">0</span>/160 ký tự</p>
                            </div>

                            <div>
                                <x-label for="focus_keyword">Từ khoá chính</x-label>
                                <x-input type="text" id="focus_keyword" name="focus_keyword" value="{{ old('focus_keyword', $post->exists ? $post->getMeta('focus_keyword') : null) }}" placeholder="vd: thiết kế website" />
                            </div>

                            <div id="seo-score" class="pt-3 border-t border-border"></div>
                            <div id="seo-checklist" class="space-y-3"></div>
                        </div>

                        <div data-tab-panel="advanced" class="space-y-4 hidden">
                            <div>
                                <x-label for="robots_directive">Chỉ mục &amp; theo dõi (Robots)</x-label>
                                @php $robotsValue = old('robots_directive', $post->exists ? $post->getMeta('robots_directive') : null); @endphp
                                <select id="robots_directive" name="robots_directive" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                    <option value="" @selected(!$robotsValue)>Mặc định (index, follow)</option>
                                    <option value="noindex_follow" @selected($robotsValue === 'noindex_follow')>Không index, vẫn theo liên kết (noindex, follow)</option>
                                    <option value="index_nofollow" @selected($robotsValue === 'index_nofollow')>Vẫn index, không theo liên kết (index, nofollow)</option>
                                    <option value="noindex_nofollow" @selected($robotsValue === 'noindex_nofollow')>Ẩn hoàn toàn (noindex, nofollow)</option>
                                </select>
                            </div>

                            <div>
                                <x-label for="canonical_url">Canonical URL (ghi đè, để trống dùng mặc định)</x-label>
                                <x-input type="url" id="canonical_url" name="canonical_url" value="{{ old('canonical_url', $post->exists ? $post->getMeta('canonical_url') : null) }}" placeholder="https://..." />
                            </div>
                        </div>
                    </div>
                </x-card>

                @if ($postType['slug'] === 'product' && $post->exists && app(\App\Support\WooCommerceClient::class)->isConfigured())
                    <x-card class="p-6 space-y-3">
                        <h2 class="font-heading text-sm font-bold">WooCommerce</h2>
                        @php $wooError = $post->getMeta('woocommerce_sync_error'); @endphp
                        @if ($wooError)
                            <p class="text-xs text-amber-600">{{ $wooError }}</p>
                        @elseif ($post->getMeta('woocommerce_synced_at'))
                            <p class="text-xs text-muted-foreground">Đã đồng bộ lúc {{ \Illuminate\Support\Carbon::parse($post->getMeta('woocommerce_synced_at'))->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="text-xs text-muted-foreground">Chưa đồng bộ — sẽ tự đồng bộ ở lần lưu tiếp theo, hoặc bấm nút bên dưới.</p>
                        @endif
                        {{-- Nút thường (KHÔNG phải <form> lồng trong content-form
                             bao ngoài) — 2 <form> lồng nhau là HTML không hợp lệ,
                             trình duyệt sẽ gộp nút này vào form ngoài cùng và
                             submit nhầm toàn bộ nội dung bài viết lên route
                             resync thay vì gọi đúng endpoint. Dùng fetch() thay
                             thế, tải lại trang sau khi xong để thấy flash message
                             (session-based) như các luồng khác trong app. --}}
                        <button type="button" id="woocommerce-resync-btn" data-url="{{ route('admin.woocommerce.resync', $post) }}" class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-accent">Đồng bộ lại</button>
                    </x-card>

                    @push('scripts')
                        <script>
                            document.getElementById('woocommerce-resync-btn')?.addEventListener('click', function () {
                                this.disabled = true;
                                fetch(this.dataset.url, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value },
                                }).finally(() => window.location.reload());
                            });
                        </script>
                    @endpush
                @endif

                @push('scripts')
                    <script>
                        // Real-time on-page SEO analyzer — same algorithm/thresholds as
                        // Rank Math's actual open-source content-analyzer (verified
                        // against its source, not guessed), adapted to PHP-free vanilla
                        // JS since this editor has no build step of its own. Two rules
                        // were deliberately dropped rather than faked: Flesch Reading
                        // Ease (an English-syllable-counting formula — meaningless
                        // applied to Vietnamese) and the word-list "title sentiment"
                        // check (too low-signal to be worth the false precision).
                        (function () {
                            const titleEl = document.getElementById('title');
                            const slugEl = document.getElementById('slug');
                            const metaTitleEl = document.getElementById('meta_title');
                            const metaDescEl = document.getElementById('meta_description');
                            const keywordEl = document.getElementById('focus_keyword');
                            const featuredImageEl = document.getElementById('featured_image');
                            const scoreEl = document.getElementById('seo-score');
                            const checklistEl = document.getElementById('seo-checklist');
                            const metaDescCountEl = document.getElementById('meta-desc-count');

                            const POWER_WORDS = ['miễn phí', 'tốt nhất', 'hướng dẫn', 'bí quyết', 'nhanh chóng', 'hiệu quả', 'chuyên nghiệp', 'đơn giản', 'dễ dàng', 'mới nhất', 'toàn diện', 'uy tín', 'chất lượng', 'tiết kiệm', 'độc quyền', 'tối ưu'];

                            function getContentText() {
                                return (window.tinymce && tinymce.get('content'))
                                    ? tinymce.get('content').getContent({ format: 'text' })
                                    : (document.getElementById('content')?.value ?? '');
                            }

                            function getContentHtml() {
                                return (window.tinymce && tinymce.get('content'))
                                    ? tinymce.get('content').getContent()
                                    : (document.getElementById('content')?.value ?? '');
                            }

                            // Slugs are always unaccented ASCII (Laravel's Str::slug
                            // transliterates on save), but a Vietnamese focus keyword
                            // is typed WITH diacritics — comparing them directly would
                            // make "keyword in slug" fail permanently regardless of
                            // what the user does. Mirrors Str::slug's own approach:
                            // Unicode NFD decomposes accented letters into base +
                            // combining mark, so stripping combining marks recovers
                            // the same ASCII base Laravel's slugifier produces (Vietnamese
                            // vowel modifiers ă/â/ê/ô/ơ/ư decompose this way too — only
                            // đ/Đ don't, so it's mapped by hand).
                            function toSlugForm(str) {
                                // Filters out Unicode combining marks (U+0300–U+036F)
                                // by codepoint rather than a regex range literal —
                                // avoids any risk of the diacritical range getting
                                // mangled by source-encoding round-trips.
                                return Array.from(str.normalize('NFD'))
                                    .filter((ch) => {
                                        const code = ch.codePointAt(0);
                                        return code < 0x0300 || code > 0x036f;
                                    })
                                    .join('')
                                    .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                                    .toLowerCase();
                            }

                            function words(text) {
                                return text.trim().split(/\s+/).filter(Boolean);
                            }

                            function escapeRegex(s) {
                                return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            }

                            function countOccurrences(haystack, needle) {
                                if (!needle) return 0;
                                const re = new RegExp(escapeRegex(needle), 'gi');
                                return (haystack.match(re) || []).length;
                            }

                            function parseHtml(html) {
                                const div = document.createElement('div');
                                div.innerHTML = html;
                                return div;
                            }

                            function isInternalLink(href) {
                                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return null;
                                if (/^\/(?!\/)/.test(href)) return true;
                                try {
                                    return new URL(href, window.location.href).hostname === window.location.hostname;
                                } catch {
                                    return true;
                                }
                            }

                            // status: 'pass' | 'warn' | 'fail'. points/max feed the score bar.
                            function pushCheck(list, { label, status, note, points, max }) {
                                list.push({ label, status, note, points, max });
                                return { points, max };
                            }

                            function renderGroup(title, checks) {
                                if (!checks.length) return '';
                                const dotClass = { pass: 'bg-brand-green', warn: 'bg-amber-500', fail: 'bg-destructive' };
                                const rows = checks.map((c) => `
                                    <div class="flex items-start gap-2 text-xs py-0.5">
                                        <span class="mt-1 h-1.5 w-1.5 rounded-full shrink-0 ${dotClass[c.status]}"></span>
                                        <span class="${c.status === 'pass' ? 'text-muted-foreground' : 'text-foreground'}">${c.label}${c.note ? ` — <span class="text-muted-foreground">${c.note}</span>` : ''}</span>
                                    </div>
                                `).join('');
                                return `<div><p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">${title}</p>${rows}</div>`;
                            }

                            window.updateSeoScore = function () {
                                const title = (metaTitleEl.value || titleEl.value || '').trim();
                                const desc = (metaDescEl.value || '').trim();
                                const keyword = keywordEl.value.trim().toLowerCase();
                                const slug = (slugEl.value || '').trim();
                                const contentText = getContentText();
                                const contentHtml = getContentHtml();
                                const wc = words(contentText).length;

                                metaDescCountEl.textContent = desc.length;

                                let earned = 0;
                                let possible = 0;
                                const keywordChecks = [];
                                const contentChecks = [];
                                const titleChecks = [];

                                // — Từ khoá chính (only meaningful once a keyword is set) —
                                if (keyword) {
                                    const titleLower = title.toLowerCase();
                                    const halfTitle = titleLower.slice(0, Math.ceil(titleLower.length / 2));

                                    let r = pushCheck(keywordChecks, { label: 'Từ khoá trong tiêu đề SEO', status: titleLower.includes(keyword) ? 'pass' : 'fail', points: titleLower.includes(keyword) ? 8 : 0, max: 8 });
                                    earned += r.points; possible += r.max;

                                    r = pushCheck(keywordChecks, { label: 'Từ khoá gần đầu tiêu đề', status: halfTitle.includes(keyword) ? 'pass' : 'warn', points: halfTitle.includes(keyword) ? 4 : 0, max: 4 });
                                    earned += r.points; possible += r.max;

                                    const descHas = desc.toLowerCase().includes(keyword);
                                    r = pushCheck(keywordChecks, { label: 'Từ khoá trong mô tả SEO', status: descHas ? 'pass' : 'fail', points: descHas ? 6 : 0, max: 6 });
                                    earned += r.points; possible += r.max;

                                    const slugHas = toSlugForm(slug).includes(toSlugForm(keyword).replace(/\s+/g, '-'));
                                    r = pushCheck(keywordChecks, { label: 'Từ khoá trong đường dẫn (slug)', status: slugHas ? 'pass' : 'fail', points: slugHas ? 6 : 0, max: 6 });
                                    earned += r.points; possible += r.max;

                                    const contentHas = contentText.toLowerCase().includes(keyword);
                                    r = pushCheck(keywordChecks, { label: 'Từ khoá xuất hiện trong nội dung', status: contentHas ? 'pass' : 'fail', points: contentHas ? 6 : 0, max: 6 });
                                    earned += r.points; possible += r.max;

                                    if (wc > 400) {
                                        const firstTenPct = words(contentText).slice(0, Math.floor(wc * 0.1)).join(' ').toLowerCase();
                                        const inFirst10 = firstTenPct.includes(keyword);
                                        r = pushCheck(keywordChecks, { label: 'Từ khoá xuất hiện trong 10% đầu nội dung', status: inFirst10 ? 'pass' : 'warn', points: inFirst10 ? 4 : 0, max: 4 });
                                        earned += r.points; possible += r.max;
                                    }

                                    const occurrences = countOccurrences(contentText, keyword);
                                    const density = wc > 0 ? (occurrences / wc) * 100 : 0;
                                    let densityStatus, densityPoints, densityNote;
                                    if (density < 0.5) { densityStatus = 'fail'; densityPoints = 0; densityNote = `${density.toFixed(2)}% — quá thấp`; }
                                    else if (density <= 0.75) { densityStatus = 'warn'; densityPoints = 3; densityNote = `${density.toFixed(2)}% — hơi thấp`; }
                                    else if (density <= 1.0) { densityStatus = 'pass'; densityPoints = 6; densityNote = `${density.toFixed(2)}% — tốt`; }
                                    else if (density <= 2.5) { densityStatus = 'pass'; densityPoints = 8; densityNote = `${density.toFixed(2)}% — rất tốt`; }
                                    else { densityStatus = 'fail'; densityPoints = 0; densityNote = `${density.toFixed(2)}% — quá dày, dễ bị coi là nhồi nhét từ khoá`; }
                                    r = pushCheck(keywordChecks, { label: 'Mật độ từ khoá (mục tiêu ~1%)', status: densityStatus, note: densityNote, points: densityPoints, max: 8 });
                                    earned += r.points; possible += r.max;

                                    const dom = parseHtml(contentHtml);
                                    const inHeadings = Array.from(dom.querySelectorAll('h2, h3, h4, h5, h6')).some((h) => h.textContent.toLowerCase().includes(keyword));
                                    r = pushCheck(keywordChecks, { label: 'Từ khoá trong 1 tiêu đề phụ (H2–H6)', status: inHeadings ? 'pass' : 'warn', points: inHeadings ? 5 : 0, max: 5 });
                                    earned += r.points; possible += r.max;

                                    const inAlt = Array.from(dom.querySelectorAll('img')).some((img) => (img.getAttribute('alt') || '').toLowerCase().includes(keyword));
                                    r = pushCheck(keywordChecks, { label: 'Từ khoá trong mô tả ảnh (alt text)', status: inAlt ? 'pass' : 'warn', points: inAlt ? 4 : 0, max: 4 });
                                    earned += r.points; possible += r.max;
                                } else {
                                    keywordChecks.push({ label: 'Nhập từ khoá chính để xem đầy đủ phân tích', status: 'warn' });
                                }

                                // — Nội dung & liên kết (always evaluated) —
                                const dom = parseHtml(contentHtml);

                                let lenStatus, lenPoints, lenNote;
                                if (wc < 600) { lenStatus = 'fail'; lenPoints = 0; lenNote = `${wc} từ — nên tối thiểu 600 từ`; }
                                else if (wc < 1000) { lenStatus = 'warn'; lenPoints = 4; lenNote = `${wc} từ — khá, có thể mở rộng`; }
                                else if (wc < 1500) { lenStatus = 'warn'; lenPoints = 6; lenNote = `${wc} từ — tốt`; }
                                else if (wc < 2000) { lenStatus = 'pass'; lenPoints = 8; lenNote = `${wc} từ — rất tốt`; }
                                else { lenStatus = 'pass'; lenPoints = 10; lenNote = `${wc} từ — xuất sắc`; }
                                let r = pushCheck(contentChecks, { label: 'Độ dài nội dung', status: lenStatus, note: lenNote, points: lenPoints, max: 10 });
                                earned += r.points; possible += r.max;

                                const links = Array.from(dom.querySelectorAll('a[href]'));
                                const hasInternal = links.some((a) => isInternalLink(a.getAttribute('href')) === true);
                                const hasExternal = links.some((a) => isInternalLink(a.getAttribute('href')) === false);
                                r = pushCheck(contentChecks, { label: 'Có ít nhất 1 liên kết nội bộ', status: hasInternal ? 'pass' : 'warn', points: hasInternal ? 6 : 0, max: 6 });
                                earned += r.points; possible += r.max;
                                r = pushCheck(contentChecks, { label: 'Có ít nhất 1 liên kết ra ngoài (nguồn tham khảo)', status: hasExternal ? 'pass' : 'warn', points: hasExternal ? 4 : 0, max: 4 });
                                earned += r.points; possible += r.max;

                                const paragraphs = Array.from(dom.querySelectorAll('p')).map((p) => words(p.textContent).length);
                                const longestParagraph = paragraphs.length ? Math.max(...paragraphs) : 0;
                                const shortParas = longestParagraph <= 120;
                                r = pushCheck(contentChecks, { label: 'Đoạn văn không quá dài (≤120 từ/đoạn)', status: shortParas ? 'pass' : 'warn', note: longestParagraph ? `đoạn dài nhất: ${longestParagraph} từ` : undefined, points: shortParas ? 5 : 0, max: 5 });
                                earned += r.points; possible += r.max;

                                const hasAsset = dom.querySelectorAll('img, video, iframe').length > 0;
                                r = pushCheck(contentChecks, { label: 'Có ít nhất 1 ảnh hoặc video minh hoạ', status: hasAsset ? 'pass' : 'warn', points: hasAsset ? 5 : 0, max: 5 });
                                earned += r.points; possible += r.max;

                                r = pushCheck(contentChecks, { label: 'Có ảnh đại diện', status: (featuredImageEl && featuredImageEl.value) ? 'pass' : 'warn', points: (featuredImageEl && featuredImageEl.value) ? 4 : 0, max: 4 });
                                earned += r.points; possible += r.max;

                                // — Tiêu đề & URL (always evaluated) —
                                const slugOk = slug.length === 0 || slug.length <= 75;
                                r = pushCheck(titleChecks, { label: 'Đường dẫn (slug) không quá dài', status: slugOk ? 'pass' : 'warn', note: slug ? `${slug.length} ký tự` : undefined, points: slugOk ? 4 : 0, max: 4 });
                                earned += r.points; possible += r.max;

                                const titleLenOk = title.length >= 30 && title.length <= 60;
                                r = pushCheck(titleChecks, { label: 'Tiêu đề SEO dài 30–60 ký tự', status: titleLenOk ? 'pass' : 'warn', note: `${title.length} ký tự`, points: titleLenOk ? 6 : 0, max: 6 });
                                earned += r.points; possible += r.max;

                                const descLenOk = desc.length >= 50 && desc.length <= 160;
                                r = pushCheck(titleChecks, { label: 'Mô tả SEO dài 50–160 ký tự', status: descLenOk ? 'pass' : 'warn', note: `${desc.length} ký tự`, points: descLenOk ? 6 : 0, max: 6 });
                                earned += r.points; possible += r.max;

                                const hasNumber = /\d/.test(title);
                                r = pushCheck(titleChecks, { label: 'Tiêu đề có chứa con số', status: hasNumber ? 'pass' : 'warn', points: hasNumber ? 3 : 0, max: 3 });
                                earned += r.points; possible += r.max;

                                const hasPowerWord = POWER_WORDS.some((w) => title.toLowerCase().includes(w));
                                r = pushCheck(titleChecks, { label: 'Tiêu đề có từ ngữ thu hút (power word)', status: hasPowerWord ? 'pass' : 'warn', points: hasPowerWord ? 4 : 0, max: 4 });
                                earned += r.points; possible += r.max;

                                const score = possible > 0 ? Math.round((earned / possible) * 100) : 0;
                                const band = score >= 80 ? { label: 'Tốt', bg: 'bg-brand-green', text: 'text-brand-green' } : score >= 50 ? { label: 'Khá', bg: 'bg-amber-500', text: 'text-amber-600' } : { label: 'Cần cải thiện', bg: 'bg-destructive', text: 'text-destructive' };

                                scoreEl.innerHTML = `
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-sm font-semibold">Điểm SEO tổng thể</span>
                                        <span class="text-sm font-bold ${band.text}">${score}/100 · ${band.label}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-muted overflow-hidden">
                                        <div class="h-full ${band.bg} transition-all" style="width: ${score}%"></div>
                                    </div>
                                `;

                                checklistEl.innerHTML = [
                                    renderGroup('Từ khoá chính', keywordChecks),
                                    renderGroup('Nội dung & liên kết', contentChecks),
                                    renderGroup('Tiêu đề & URL', titleChecks),
                                ].join('');
                            };

                            [titleEl, slugEl, metaTitleEl, metaDescEl, keywordEl, featuredImageEl].forEach((el) => {
                                el?.addEventListener('input', () => window.updateSeoScore());
                            });

                            window.updateSeoScore();
                        })();
                    </script>
                @endpush
            </div>
        </div>
    </form>
@endsection
