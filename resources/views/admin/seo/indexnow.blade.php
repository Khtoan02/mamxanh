@extends('layouts.admin')

@section('title', 'SEO — IndexNow')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-1">SEO &amp; GEO</h2>
    <p class="text-sm text-muted-foreground mb-6">Thiết lập mẫu tiêu đề/mô tả chung cho toàn bộ site.</p>

    @include('admin.seo._tabs', ['active' => 'indexnow'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form id="seo-indexnow-form" method="POST" action="{{ route('admin.seo.indexnow.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">IndexNow</h2>
            <p class="text-xs text-muted-foreground">
                Khi bật, mỗi lần xuất bản/cập nhật bài viết sẽ tự động báo cho Bing/Yandex biết ngay qua giao thức IndexNow — giúp các công cụ này index nhanh hơn thay vì chờ tự crawl. Không ảnh hưởng gì tới Google (Google không dùng giao thức này).
            </p>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="indexnow_enabled" value="0">
                <input type="checkbox" name="indexnow_enabled" value="1" @checked(old('indexnow_enabled', $enabled)) class="rounded border-input">
                Tự động ping IndexNow khi xuất bản
            </label>
            <p class="text-xs text-muted-foreground">
                Key xác thực (tự tạo, hiện thật tại <a href="{{ url("/{$indexNowKey}.txt") }}" target="_blank" class="text-primary hover:underline font-mono">{{ url("/{$indexNowKey}.txt") }}</a>):
                <code class="block mt-1 rounded bg-muted px-2 py-1 font-mono text-xs break-all">{{ $indexNowKey }}</code>
            </p>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
