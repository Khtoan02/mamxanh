@extends('layouts.admin')

@section('title', 'SEO — Sơ đồ trang')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-1">SEO &amp; GEO</h2>
    <p class="text-sm text-muted-foreground mb-6">Thiết lập mẫu tiêu đề/mô tả chung cho toàn bộ site.</p>

    @include('admin.seo._tabs', ['active' => 'sitemap'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form id="seo-sitemap-form" method="POST" action="{{ route('admin.seo.sitemap.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Sitemap.xml</h2>
            <p class="text-xs text-muted-foreground">
                Xem tại <a href="{{ url('/sitemap.xml') }}" target="_blank" class="text-primary hover:underline">{{ url('/sitemap.xml') }}</a>.
                Bỏ chọn loại nội dung nào để loại nó khỏi sitemap (vẫn hiển thị bình thường trên site, chỉ không khai báo cho công cụ tìm kiếm qua sitemap).
            </p>
            <div class="space-y-2">
                @foreach ($postTypes as $slug => $type)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="included[]" value="{{ $slug }}" @checked(! in_array($slug, $excluded, true)) class="rounded border-input">
                        {{ $type['label'] }}
                    </label>
                @endforeach
            </div>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
