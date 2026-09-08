@extends('layouts.admin')

@php
    $ownerPostType = get_post_type($taxonomy['post_types'][0] ?? '');
@endphp

@section('title', ($ownerPostType['label'] ?? $taxonomy['label']).' — Danh mục & Thẻ')

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <div class="flex gap-1 rounded-lg border border-border bg-muted p-1 mb-5 w-fit">
        @foreach ($allTaxonomies as $slug => $def)
            <a href="{{ route('admin.taxonomies.index', ['taxonomy' => $slug]) }}"
               class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $taxonomy['slug'] === $slug ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                {{ $def['label'] }}
            </a>
        @endforeach
    </div>

    <x-card class="p-6 mb-6">
        <form method="POST" action="{{ route('admin.taxonomies.store', ['taxonomy' => $taxonomy['slug']]) }}" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <x-label for="name">Thêm {{ $taxonomy['label'] }} mới</x-label>
                <x-input type="text" id="name" name="name" placeholder="Tên" required />
            </div>
            <x-button type="submit" variant="primary" class="!w-auto px-6 shrink-0">Thêm</x-button>
        </form>
    </x-card>

    <x-card class="overflow-hidden">
        @if ($terms->isEmpty())
            <div class="p-10 text-center text-sm text-muted-foreground">Chưa có {{ mb_strtolower($taxonomy['label']) }} nào.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Tên</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">Số bài dùng</th>
                        <th class="px-4 py-3 font-medium text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($terms as $term)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $term->name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $term->slug }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $term->posts_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" class="text-xs text-primary hover:underline mr-3"
                                    data-term-edit-trigger
                                    data-action="{{ route('admin.taxonomies.update', ['taxonomy' => $taxonomy['slug'], 'term' => $term]) }}"
                                    data-name="{{ $term->name }}"
                                    data-slug="{{ $term->slug }}"
                                    data-description="{{ $term->description }}">
                                    Sửa
                                </button>
                                <form method="POST" action="{{ route('admin.taxonomies.destroy', ['taxonomy' => $taxonomy['slug'], 'term' => $term]) }}" class="inline" onsubmit="return confirm('Xoá \'{{ $term->name }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-destructive hover:underline">Xoá</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-card>

    {{-- Edit modal — 1 modal dùng chung cho mọi hàng, populate lại bằng JS
         từ data-* của nút "Sửa" vừa bấm (xem initTaxonomyEditModal). --}}
    <div id="term-edit-overlay" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4" tabindex="-1">
        <x-card class="w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-heading text-sm font-bold">Sửa {{ $taxonomy['label_singular'] ?? $taxonomy['label'] }}</h2>
                <button type="button" id="term-edit-close" class="text-muted-foreground hover:text-foreground">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <form id="term-edit-form" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-label for="term-edit-name">Tên</x-label>
                    <x-input type="text" id="term-edit-name" name="name" required />
                </div>
                <div>
                    <x-label for="term-edit-slug">Slug (để trống để tự tạo từ tên)</x-label>
                    <x-input type="text" id="term-edit-slug" name="slug" placeholder="vi-du-ten" />
                </div>
                <div>
                    <x-label for="term-edit-description">Mô tả</x-label>
                    <textarea id="term-edit-description" name="description" rows="3" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
                </div>

                <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu</x-button>
            </form>
        </x-card>
    </div>
@endsection
