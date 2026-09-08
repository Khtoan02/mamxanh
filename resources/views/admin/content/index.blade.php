@extends('layouts.admin')

@section('title', $postType['label'])

@php
    $hasImages = in_array('featured_image', $postType['supports'], true);
@endphp

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="font-heading text-lg font-bold">{{ $postType['label'] }}</h2>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.content.index') }}" id="content-search-form">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="search" name="q" value="{{ $search }}" id="content-search-input" placeholder="Tìm theo tiêu đề…"
                       class="w-56 rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">
            </form>
            @if ($hasImages)
                <div class="flex gap-1 rounded-lg border border-border bg-muted p-1 shrink-0" id="content-view-toggle" data-type="{{ $type }}">
                    <button type="button" data-view="table" class="content-view-btn rounded-md p-1.5 hover:text-foreground" title="Dạng bảng">
                        <x-icon name="list" class="h-4 w-4" />
                    </button>
                    <button type="button" data-view="grid" class="content-view-btn rounded-md p-1.5 hover:text-foreground" title="Dạng lưới">
                        <x-icon name="grid" class="h-4 w-4" />
                    </button>
                </div>
            @endif
            @if ($type === 'product' && auth()->user()->can('manage_options') && app(\App\Support\WooCommerceClient::class)->isConfigured())
                <form method="POST" action="{{ route('admin.woocommerce.import') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-accent shrink-0">
                        Nhập từ WooCommerce
                    </button>
                </form>
            @endif
            @can('edit_posts')
                <a href="{{ route('admin.content.create', ['type' => $type]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90 shrink-0">
                    + Thêm {{ mb_strtolower($postType['label_singular']) }}
                </a>
            @endcan
        </div>
    </div>

    @if ($posts->isNotEmpty())
        <div id="content-bulk-toolbar" class="hidden mb-3 items-center justify-between gap-3 rounded-lg border border-primary/40 bg-primary/5 px-4 py-2.5"
             data-destroy-url="{{ route('admin.content.bulkDestroy') }}" data-update-url="{{ route('admin.content.bulkUpdate') }}">
            <p class="text-sm font-medium"><span id="content-bulk-count">0</span> mục đã chọn</p>
            <div class="flex items-center gap-4">
                <button type="button" id="content-bulk-clear" class="text-sm text-muted-foreground hover:text-foreground">Bỏ chọn</button>
                @can('edit_posts')
                    <button type="button" id="content-bulk-edit-open" class="text-sm font-medium text-primary hover:underline">Sửa hàng loạt</button>
                @endcan
                @can('delete_posts')
                    <button type="button" id="content-bulk-delete" class="inline-flex items-center gap-1.5 rounded-lg bg-destructive px-3 py-1.5 text-sm font-semibold text-destructive-foreground hover:opacity-90">
                        <x-icon name="trash-2" class="h-4 w-4" /> Xoá đã chọn
                    </button>
                @endcan
            </div>
        </div>
    @endif

    @if ($posts->isEmpty() && $search !== '')
        <x-card class="p-10 text-center text-sm text-muted-foreground">
            Không tìm thấy {{ mb_strtolower($postType['label']) }} nào khớp với "{{ $search }}".
        </x-card>
    @elseif ($posts->isEmpty())
        <x-card class="p-10 text-center text-sm text-muted-foreground">
            Chưa có {{ mb_strtolower($postType['label']) }} nào. Bấm "+ Thêm {{ mb_strtolower($postType['label_singular']) }}" để tạo cái đầu tiên.
        </x-card>
    @else
        {{-- Dạng bảng --}}
        <x-card class="overflow-hidden" id="content-view-table" data-content-view="table">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-muted-foreground">
                        <th class="w-8 px-4 py-3"></th>
                        @if ($hasImages)
                            <th class="w-16 px-2 py-3"></th>
                        @endif
                        <th class="px-4 py-3 font-medium">Tiêu đề</th>
                        @if ($primaryTaxonomy)
                            <th class="px-4 py-3 font-medium">{{ $primaryTaxonomy['label'] }}</th>
                        @endif
                        @if ($listColumn)
                            <th class="px-4 py-3 font-medium">{{ $listColumn['label'] }}</th>
                        @endif
                        <th class="px-4 py-3 font-medium">Trạng thái</th>
                        <th class="px-4 py-3 font-medium">Tác giả</th>
                        <th class="px-4 py-3 font-medium">Ngày</th>
                        <th class="px-4 py-3 font-medium text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($posts as $post)
                        @php $primaryTerm = $primaryTaxonomy ? $post->terms->firstWhere('taxonomy', $primaryTaxonomy['slug']) : null; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" class="content-select-checkbox h-3.5 w-3.5 accent-primary cursor-pointer" data-post-id="{{ $post->id }}">
                            </td>
                            @if ($hasImages)
                                <td class="px-2 py-3">
                                    @if ($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="" class="h-10 w-10 rounded-lg object-cover border border-border">
                                    @else
                                        <div class="h-10 w-10 rounded-lg bg-muted flex items-center justify-center">
                                            <x-icon name="image" class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 font-medium">{{ $post->title }}</td>
                            @if ($primaryTaxonomy)
                                <td class="px-4 py-3 text-muted-foreground">{{ $primaryTerm->name ?? '—' }}</td>
                            @endif
                            @if ($listColumn)
                                <td class="px-4 py-3 text-muted-foreground">{{ $post->getMeta($listColumn['key']) ?: '—' }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <x-badge :variant="$post->status === 'published' ? 'green' : 'secondary'">
                                    {{ $post->status === 'published' ? 'Đã đăng' : 'Nháp' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $post->author->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $post->updated_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3 text-sm">
                                    <a href="{{ route('post.show', $post->slug) }}" target="_blank" class="text-muted-foreground hover:text-foreground hover:underline">Xem</a>
                                    @can('edit_posts')
                                        <a href="{{ route('admin.content.edit', $post) }}" class="text-primary hover:underline">Sửa</a>
                                    @endcan
                                    @can('delete_posts')
                                        <form method="POST" action="{{ route('admin.content.destroy', $post) }}" onsubmit="return confirm('Xoá \'{{ $post->title }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-destructive hover:underline">Xoá</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>

        {{-- Dạng lưới — chỉ có ý nghĩa cho loại nội dung có ảnh đại diện --}}
        @if ($hasImages)
            <div id="content-view-grid" data-content-view="grid" class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach ($posts as $post)
                    @php $primaryTerm = $primaryTaxonomy ? $post->terms->firstWhere('taxonomy', $primaryTaxonomy['slug']) : null; @endphp
                    <x-card class="overflow-hidden relative">
                        <label class="absolute top-2 left-2 z-10 flex h-5 w-5 items-center justify-center rounded bg-black/70">
                            <input type="checkbox" class="content-select-checkbox h-3.5 w-3.5 accent-primary cursor-pointer" data-post-id="{{ $post->id }}">
                        </label>
                        <a href="{{ route('admin.content.edit', $post) }}" class="block">
                            <div class="relative aspect-square bg-muted">
                                @if ($post->featured_image)
                                    <img src="{{ $post->featured_image }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <x-icon name="image" class="h-8 w-8 text-muted-foreground" />
                                    </div>
                                @endif
                                <span class="absolute top-2 right-2">
                                    <x-badge :variant="$post->status === 'published' ? 'green' : 'secondary'">
                                        {{ $post->status === 'published' ? 'Đã đăng' : 'Nháp' }}
                                    </x-badge>
                                </span>
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent px-3 pt-8 pb-2">
                                    <p class="text-sm font-medium text-white truncate">{{ $post->title }}</p>
                                    @if ($listColumn && $post->getMeta($listColumn['key']))
                                        <p class="text-xs text-white/80">{{ $post->getMeta($listColumn['key']) }}</p>
                                    @elseif ($primaryTerm)
                                        <p class="text-xs text-white/80">{{ $primaryTerm->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <div class="flex items-center justify-between px-3 py-2 text-xs">
                            <a href="{{ route('post.show', $post->slug) }}" target="_blank" class="text-muted-foreground hover:text-foreground hover:underline">Xem</a>
                            @can('delete_posts')
                                <form method="POST" action="{{ route('admin.content.destroy', $post) }}" onsubmit="return confirm('Xoá \'{{ $post->title }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-destructive hover:underline">Xoá</button>
                                </form>
                            @endcan
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    @endif

    <div class="mt-4">
        {{ $posts->links() }}
    </div>

    {{-- Modal sửa hàng loạt --}}
    @can('edit_posts')
        <div id="content-bulk-edit-overlay" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" tabindex="-1">
            <x-card class="w-full max-w-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-heading text-sm font-bold">Sửa hàng loạt (<span id="content-bulk-edit-count">0</span> mục)</h2>
                    <button type="button" id="content-bulk-edit-close" class="text-muted-foreground hover:text-foreground">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <p class="text-xs text-muted-foreground">Chỉ áp dụng field bạn thật sự chọn — để "Giữ nguyên" thì bỏ qua, không đụng tới.</p>

                <div>
                    <x-label for="bulk-edit-status">Trạng thái</x-label>
                    <select id="bulk-edit-status" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">— Giữ nguyên —</option>
                        <option value="draft">Nháp</option>
                        <option value="published">Đã đăng</option>
                    </select>
                </div>

                @if ($primaryTaxonomy)
                    <div>
                        <x-label for="bulk-edit-term">{{ $primaryTaxonomy['label'] }}</x-label>
                        <select id="bulk-edit-term" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                            <option value="">— Giữ nguyên —</option>
                            @foreach ($primaryTaxonomyTerms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <x-button type="button" id="content-bulk-edit-submit" variant="primary" class="!w-auto px-6">Áp dụng</x-button>
            </x-card>
        </div>
    @endcan

    @push('scripts')
        <script>
            (function () {
                const input = document.getElementById('content-search-input');
                let timer = null;
                input?.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => document.getElementById('content-search-form').submit(), 400);
                });
            })();
        </script>
    @endpush
@endsection
