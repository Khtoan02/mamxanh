@extends('layouts.admin')

@section('title', 'SEO — Tiêu đề & Meta')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-1">SEO &amp; GEO</h2>
    <p class="text-sm text-muted-foreground mb-6">Thiết lập mẫu tiêu đề/mô tả chung cho toàn bộ site.</p>

    @include('admin.seo._tabs', ['active' => 'titles'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form id="seo-titles-form" method="POST" action="{{ route('admin.seo.titles.update') }}" class="space-y-5 max-w-3xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Biến khả dụng</h2>
            <p class="text-xs text-muted-foreground leading-relaxed">
                Dùng trong mọi mẫu bên dưới: <code class="rounded bg-muted px-1">%title%</code> tiêu đề nội dung,
                <code class="rounded bg-muted px-1">%term%</code> tên loại (vd "Dịch vụ"),
                <code class="rounded bg-muted px-1">%sitename%</code> tên site,
                <code class="rounded bg-muted px-1">%tagline%</code> khẩu hiệu,
                <code class="rounded bg-muted px-1">%sep%</code> dấu phân cách,
                <code class="rounded bg-muted px-1">%currentyear%</code> năm hiện tại.
            </p>
            <div>
                <x-label for="seo_sep">Dấu phân cách</x-label>
                <x-input type="text" id="seo_sep" name="seo_sep" value="{{ old('seo_sep', $sep) }}" maxlength="20" class="max-w-[120px] template-input" data-preview="preview-sep" />
            </div>
        </x-card>

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Theo từng loại nội dung</h2>
            <p class="text-xs text-muted-foreground">Áp dụng cho trang chi tiết Bài viết/Trang/Sản phẩm/Dịch vụ/Dự án.</p>

            @foreach ($types as $type => $label)
                <div>
                    <x-label for="seo_title_{{ $type }}">{{ $label }}</x-label>
                    <x-input type="text" id="seo_title_{{ $type }}" name="seo_title_{{ $type }}"
                        value="{{ old('seo_title_'.$type, $templates[$type]) }}"
                        maxlength="255" class="template-input"
                        data-preview="preview-{{ $type }}"
                        data-sample-title="{{ $type === 'product' ? 'Loa Bluetooth Mini' : ($type === 'service' ? 'Thiết kế Website' : ($type === 'project' ? 'Dự án ABC Corp' : 'Bài viết mẫu')) }}" />
                    <p id="preview-{{ $type }}" class="mt-1 text-xs text-muted-foreground font-mono"></p>
                </div>
            @endforeach
        </x-card>

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Trang chủ &amp; trang liệt kê</h2>
            <div>
                <x-label for="seo_title_home">Tiêu đề trang chủ</x-label>
                <x-input type="text" id="seo_title_home" name="seo_title_home" value="{{ old('seo_title_home', $titleHome) }}" maxlength="255" class="template-input" data-preview="preview-home-title" />
                <p id="preview-home-title" class="mt-1 text-xs text-muted-foreground font-mono"></p>
            </div>
            <div>
                <x-label for="seo_desc_home">Mô tả trang chủ</x-label>
                <x-input type="text" id="seo_desc_home" name="seo_desc_home" value="{{ old('seo_desc_home', $descHome) }}" maxlength="255" class="template-input" data-preview="preview-home-desc" />
                <p id="preview-home-desc" class="mt-1 text-xs text-muted-foreground font-mono"></p>
            </div>
            <div>
                <x-label for="seo_title_archive">Tiêu đề trang liệt kê (Blog/Dịch vụ/Dự án/Sản phẩm)</x-label>
                <x-input type="text" id="seo_title_archive" name="seo_title_archive" value="{{ old('seo_title_archive', $titleArchive) }}" maxlength="255" class="template-input" data-preview="preview-archive-title" data-sample-title="Dịch vụ" />
                <p id="preview-archive-title" class="mt-1 text-xs text-muted-foreground font-mono"></p>
            </div>
            <div>
                <x-label for="seo_desc_archive">Mô tả trang liệt kê</x-label>
                <x-input type="text" id="seo_desc_archive" name="seo_desc_archive" value="{{ old('seo_desc_archive', $descArchive) }}" maxlength="255" class="template-input" data-preview="preview-archive-desc" data-sample-title="Dịch vụ" />
                <p id="preview-archive-desc" class="mt-1 text-xs text-muted-foreground font-mono"></p>
            </div>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>

    @push('scripts')
        <script>
            (function () {
                const sitename = @json(config('app.name'));
                const tagline = @json(env('SITE_TAGLINE') ?: config('app.name'));

                function render(template, vars) {
                    const sep = document.getElementById('seo_sep').value || ' — ';
                    const all = Object.assign({ sitename, tagline, sep, currentyear: String(new Date().getFullYear()) }, vars);
                    return template.replace(/%([a-z_]+)%/g, (m, key) => all[key] ?? '').replace(/\s+/g, ' ').trim();
                }

                function updateAll() {
                    document.querySelectorAll('.template-input').forEach((el) => {
                        const previewId = el.dataset.preview;
                        if (!previewId) return;
                        const previewEl = document.getElementById(previewId);
                        if (!previewEl) return;
                        const sample = el.dataset.sampleTitle || 'Ví dụ tiêu đề';
                        previewEl.textContent = render(el.value, { title: sample, term: sample });
                    });
                }

                document.querySelectorAll('.template-input').forEach((el) => el.addEventListener('input', updateAll));
                updateAll();
            })();
        </script>
    @endpush
@endsection
