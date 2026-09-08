@extends('layouts.admin')

@section('title', 'Thư viện media')

@section('content')
    @php
        $tabs = [
            'image' => ['label' => 'Ảnh', 'icon' => 'image'],
            'video' => ['label' => 'Video', 'icon' => 'film'],
            'pdf' => ['label' => 'PDF', 'icon' => 'file-text'],
            'file' => ['label' => 'File khác', 'icon' => 'file'],
        ];
    @endphp

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex gap-1 overflow-x-auto rounded-lg border border-border bg-muted p-1 w-fit max-w-full">
            @foreach ($tabs as $key => $tab)
                @php
                    $tabCount = $key === 'video' ? (($counts['video'] ?? 0) + ($counts['embed'] ?? 0)) : ($counts[$key] ?? 0);
                @endphp
                <a href="{{ route('admin.media.index', ['type' => $key]) }}"
                   class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors {{ $kind === $key ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                    <x-icon :name="$tab['icon']" class="h-4 w-4" />
                    {{ $tab['label'] }}
                    <span class="text-xs {{ $kind === $key ? 'text-muted-foreground' : 'text-muted-foreground/70' }}">{{ $tabCount }}</span>
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <p id="media-upload-progress" class="hidden text-sm text-muted-foreground">Đang tải lên…</p>
            <label for="media-file-input" title="Kéo thả tệp vào bất kỳ đâu trên trang để tự động nén &amp; tải lên — không giới hạn dung lượng"
                   class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90 cursor-pointer shrink-0">
                <x-icon name="upload-cloud" class="h-4 w-4" /> Tải tệp lên
            </label>
            <input type="file" id="media-file-input" name="files[]" multiple class="hidden">
        </div>
    </div>

    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" id="media-upload-form" class="hidden">
        @csrf
    </form>

    {{-- Shown only while a file is actually being dragged over the page
         (JS toggles it on #admin-main's dragenter/dragleave/drop) — the
         whole page already accepts a drop, so a permanently-visible
         "drag here" bar would just be redundant chrome the rest of the
         time. --}}
    <div id="media-dropzone-overlay" class="hidden fixed inset-0 z-40 items-center justify-center bg-background/80 backdrop-blur-sm pointer-events-none">
        <div class="rounded-xl border-2 border-dashed border-primary bg-card px-10 py-8 text-center shadow-lg">
            <x-icon name="upload-cloud" class="h-8 w-8 text-primary mx-auto mb-2" />
            <p class="text-sm font-medium">Thả tệp vào đây để tự động tải lên</p>
        </div>
    </div>

    @if ($kind === 'video')
        <form method="POST" action="{{ route('admin.media.storeEmbed') }}" class="mb-4 flex items-center gap-2">
            @csrf
            <input type="url" id="embed_url" name="embed_url" required placeholder="Dán link YouTube/Vimeo để thêm video nhúng…"
                   class="flex-1 min-w-0 rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
            <button type="submit" class="shrink-0 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-accent">Thêm video</button>
        </form>
    @endif

    @if ($media->isEmpty())
        <x-card class="p-10 text-center text-sm text-muted-foreground">
            Chưa có {{ mb_strtolower($tabs[$kind]['label']) }} nào trong thư viện.
        </x-card>
    @else
        <div id="media-bulk-toolbar" class="hidden mb-3 items-center justify-between gap-3 rounded-lg border border-primary/40 bg-primary/5 px-4 py-2.5">
            <p class="text-sm font-medium"><span id="media-bulk-count">0</span> mục đã chọn</p>
            <div class="flex items-center gap-4">
                <button type="button" id="media-bulk-clear" class="text-sm text-muted-foreground hover:text-foreground">Bỏ chọn</button>
                <form id="media-bulk-delete-form" method="POST" action="{{ route('admin.media.bulkDestroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="media-bulk-delete" class="inline-flex items-center gap-1.5 rounded-lg bg-destructive px-3 py-1.5 text-sm font-semibold text-destructive-foreground hover:opacity-90">
                        <x-icon name="trash-2" class="h-4 w-4" /> Xoá đã chọn
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-2">
            @foreach ($media as $item)
                <div class="relative">
                    <label class="absolute top-1 right-1 z-10 flex h-5 w-5 items-center justify-center rounded bg-black/70">
                        <input type="checkbox" class="media-select-checkbox h-3.5 w-3.5 accent-primary cursor-pointer" data-media-id="{{ $item->id }}">
                    </label>
                    <button type="button"
                            class="media-card group relative block w-full aspect-square overflow-hidden rounded-lg border border-border hover:border-primary/60 text-left transition-colors"
                            data-media-id="{{ $item->id }}"
                            data-name="{{ $item->original_name }}"
                            data-kind="{{ $item->kind }}"
                            data-kind-label="{{ $item->kindLabel() }}"
                            data-format="{{ $item->formatBadge() }}"
                            data-has-seo="{{ $item->hasSeoInfo() ? '1' : '0' }}"
                            data-size="{{ $item->humanSize() }}"
                            data-mime="{{ $item->mime_type ?? '—' }}"
                            data-dimensions="{{ $item->dimensions() ?? '' }}"
                            data-url="{{ $item->url() }}"
                            data-thumb="{{ $item->thumbnailUrl() }}"
                            data-alt-text="{{ $item->alt_text }}"
                            data-slug="{{ $item->slug() }}"
                            data-provider="{{ $item->embed_provider }}"
                            data-uploader="{{ $item->uploader->name ?? '—' }}"
                            data-uploader-id="{{ $item->uploaded_by }}"
                            data-created="{{ $item->created_at->format('d/m/Y H:i') }}"
                            data-updated="{{ $item->updated_at->format('d/m/Y H:i') }}"
                            data-changed="{{ $item->updated_at->ne($item->created_at) ? '1' : '0' }}"
                            data-update-url="{{ route('admin.media.update', $item) }}"
                            data-destroy-url="{{ route('admin.media.destroy', $item) }}"
                            data-used-in="{{ json_encode($item->usedIn()) }}">
                        <div class="absolute inset-0 bg-muted flex items-center justify-center">
                            @if ($item->thumbnailUrl())
                                <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->alt_text ?: $item->original_name }}" class="w-full h-full object-cover">
                            @else
                                <x-icon :name="$item->kind === 'pdf' ? 'file-text' : ($item->kind === 'video' || $item->kind === 'embed' ? 'film' : 'file')" class="h-10 w-10 text-muted-foreground" />
                            @endif
                        </div>

                        <span class="absolute top-1 left-1 rounded bg-black/70 px-1 py-0.5 text-[9px] font-bold tracking-wide text-white leading-none">{{ $item->formatBadge() }}</span>
                        <span class="media-seo-dot absolute bottom-1.5 right-1.5 h-2 w-2 rounded-full ring-1 ring-black/40 {{ $item->hasSeoInfo() ? 'bg-brand-green' : 'bg-destructive' }}" title="{{ $item->hasSeoInfo() ? 'Đã có mô tả SEO' : 'Thiếu mô tả SEO' }}"></span>

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent px-1.5 pt-6 pb-1">
                            <p class="text-[11px] font-medium text-white truncate leading-tight pr-3" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                            <p class="text-[10px] text-white/75 leading-tight">{{ $item->humanSize() }}</p>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>
        <p class="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
            <button type="button" id="media-select-all" class="font-medium text-primary hover:underline">Chọn tất cả</button>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-green"></span> Đã có mô tả SEO</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-destructive"></span> Thiếu mô tả SEO</span>
        </p>

        <div class="mt-6">
            {{ $media->links() }}
        </div>
    @endif

    {{-- Detail modal — populated from the clicked card's data-* attributes, no extra fetch needed. --}}
    <div id="media-detail-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" tabindex="-1">
        <div class="w-full max-w-4xl max-h-[92vh] overflow-y-auto rounded-xl border border-border bg-card p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-heading text-base font-bold">Chi tiết media</h2>
                <button type="button" id="media-detail-close" class="shrink-0 text-muted-foreground hover:text-foreground">
                    <x-icon name="x" />
                </button>
            </div>

            <div id="media-detail-preview" class="w-full min-h-[240px] max-h-[520px] rounded-lg bg-muted flex items-center justify-center overflow-hidden mb-3"></div>

            <div class="flex items-center gap-2 mb-3">
                <input type="text" id="media-detail-url" readonly class="flex-1 min-w-0 rounded-md border border-input bg-background px-2 py-1.5 text-xs text-muted-foreground">
                <button type="button" id="media-detail-copy" class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium hover:bg-accent">
                    <x-icon name="copy" class="h-3.5 w-3.5" /> Copy
                </button>
                <a id="media-detail-open" href="#" target="_blank" class="shrink-0 rounded-md border border-border px-3 py-1.5 text-xs font-medium hover:bg-accent">Mở tab mới</a>
                <form id="media-detail-delete-form" method="POST" onsubmit="return confirm('Xoá media này? Không thể hoàn tác.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="shrink-0 rounded-md border border-destructive/40 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/10">Xoá</button>
                </form>
            </div>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2 text-sm mb-4 rounded-lg border border-border p-3">
                <div>
                    <dt class="text-muted-foreground text-xs">Loại</dt>
                    <dd id="media-detail-kind"></dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-xs">Kích thước</dt>
                    <dd id="media-detail-size"></dd>
                </div>
                <div id="media-detail-mime-label">
                    <dt class="text-muted-foreground text-xs">Định dạng</dt>
                    <dd id="media-detail-mime"></dd>
                </div>
                <div class="hidden" id="media-detail-dimensions-label">
                    <dt class="text-muted-foreground text-xs">Kích thước ảnh</dt>
                    <dd id="media-detail-dimensions"></dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-xs">Ngày tải lên</dt>
                    <dd id="media-detail-created"></dd>
                </div>
                <div class="hidden" id="media-detail-updated-label">
                    <dt class="text-muted-foreground text-xs">Cập nhật lần cuối</dt>
                    <dd id="media-detail-updated"></dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-xs">ID</dt>
                    <dd id="media-detail-id"></dd>
                </div>
            </dl>

            <div class="min-w-0">
                    <form id="media-detail-alt-form" class="mb-4 rounded-lg border border-border bg-muted/40 p-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <p class="text-sm font-semibold flex items-center gap-1.5">
                            <x-icon name="search" class="h-3.5 w-3.5 text-muted-foreground" /> SEO &amp; siêu dữ liệu
                        </p>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <x-label for="media-detail-title-input">Tên hiển thị</x-label>
                                <input type="text" id="media-detail-title-input" name="title" placeholder="Tên tệp"
                                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                            </div>

                            <div>
                                <x-label for="media-detail-uploader-select">Người tải lên</x-label>
                                <select id="media-detail-uploader-select" name="uploaded_by"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="media-detail-slug-field">
                            <x-label for="media-detail-slug-input">Đường dẫn tệp (URL)</x-label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" id="media-detail-slug-input" name="slug" placeholder="ten-tep-mo-ta"
                                       class="flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring">
                                <span id="media-detail-slug-suffix" class="shrink-0 text-xs text-muted-foreground font-mono"></span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground/70">Đổi đường dẫn sẽ đổi tên tệp thật và tự cập nhật mọi nơi đang dùng ảnh này.</p>
                        </div>

                        <div>
                            <x-label for="media-detail-alt-input" id="media-detail-alt-label">Mô tả ảnh (alt text)</x-label>
                            <input type="text" id="media-detail-alt-input" name="alt_text" placeholder="Mô tả cho SEO / trợ năng"
                                   class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" id="media-detail-alt-save" class="shrink-0 inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-50">
                                Lưu
                            </button>
                            <p id="media-detail-alt-status" class="text-xs text-brand-green invisible">Đã lưu.</p>
                        </div>
                    </form>

                    <div class="pt-4 border-t border-border">
                        <p class="text-sm font-medium mb-2">Đang sử dụng ở</p>
                        <ul id="media-detail-usage-list" class="space-y-1 text-sm"></ul>
                        <p id="media-detail-usage-empty" class="hidden text-sm text-muted-foreground">Chưa được sử dụng ở trang nào.</p>
                    </div>
            </div>
        </div>
    </div>
@endsection
