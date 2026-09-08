@extends('layouts.admin')

@section('title', 'SEO — robots.txt')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-1">SEO &amp; GEO</h2>
    <p class="text-sm text-muted-foreground mb-6">Thiết lập mẫu tiêu đề/mô tả chung cho toàn bộ site.</p>

    @include('admin.seo._tabs', ['active' => 'robots'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form id="seo-robots-form" method="POST" action="{{ route('admin.seo.robots.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">robots.txt</h2>
            <p class="text-xs text-muted-foreground">
                Sửa trực tiếp nội dung robots.txt thật của site — có hiệu lực ngay tại
                <a href="{{ url('/robots.txt') }}" target="_blank" class="text-primary hover:underline">{{ url('/robots.txt') }}</a> sau khi lưu.
            </p>
            <div>
                <textarea id="robots_txt" name="robots_txt" rows="8" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm font-mono text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('robots_txt', $robotsTxt) }}</textarea>
            </div>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
