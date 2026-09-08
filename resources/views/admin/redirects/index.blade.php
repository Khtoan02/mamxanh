@extends('layouts.admin')

@section('title', 'Chuyển hướng (Redirect)')

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <x-card class="p-6 mb-6">
        <h2 class="font-heading text-sm font-bold mb-4">Thêm redirect mới</h2>
        <form method="POST" action="{{ route('admin.redirects.store') }}" class="grid sm:grid-cols-[1fr_1fr_auto_auto_auto] gap-3 items-end">
            @csrf
            <div>
                <x-label for="source">Đường dẫn cũ</x-label>
                <div class="flex items-center rounded-lg border border-input bg-background focus-within:ring-2 focus-within:ring-ring">
                    <span class="pl-3 text-sm text-muted-foreground">/</span>
                    <input type="text" id="source" name="source" value="{{ old('source', $prefillSource) }}" required placeholder="bai-viet-cu" class="flex-1 min-w-0 bg-transparent px-2 py-2 text-sm focus:outline-none">
                </div>
            </div>

            <div>
                <x-label for="target">Chuyển đến</x-label>
                <x-input type="text" id="target" name="target" value="{{ old('target') }}" required placeholder="/bai-viet-moi hoặc https://..." />
            </div>

            <div>
                <x-label for="match_type">Kiểu khớp</x-label>
                <select id="match_type" name="match_type" class="rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    @foreach (\App\Models\Redirect::MATCH_TYPES as $key => $label)
                        <option value="{{ $key }}" @selected(old('match_type', 'exact') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label for="status_code">Mã trạng thái</x-label>
                <select id="status_code" name="status_code" class="rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="301" @selected(old('status_code', '301') == '301')>301 (Vĩnh viễn)</option>
                    <option value="302" @selected(old('status_code') == '302')>302 (Tạm thời)</option>
                    <option value="307" @selected(old('status_code') == '307')>307 (Tạm thời, giữ method)</option>
                    <option value="308" @selected(old('status_code') == '308')>308 (Vĩnh viễn, giữ method)</option>
                </select>
            </div>

            <x-button type="submit" variant="primary" class="!w-auto px-6">Thêm</x-button>
        </form>
    </x-card>

    <x-card class="overflow-hidden">
        @if ($redirects->isEmpty())
            <div class="p-10 text-center text-sm text-muted-foreground">Chưa có redirect nào.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-xs text-muted-foreground">
                        <th class="px-4 py-3 font-medium">Đường dẫn cũ</th>
                        <th class="px-4 py-3 font-medium">Chuyển đến</th>
                        <th class="px-4 py-3 font-medium">Kiểu khớp</th>
                        <th class="px-4 py-3 font-medium">Mã</th>
                        <th class="px-4 py-3 font-medium">Số lần khớp</th>
                        <th class="px-4 py-3 font-medium text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($redirects as $redirect)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">/{{ $redirect->source }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground truncate max-w-[220px]" title="{{ $redirect->target }}">{{ $redirect->target }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ \App\Models\Redirect::MATCH_TYPES[$redirect->match_type] ?? $redirect->match_type }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $redirect->status_code }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $redirect->hits }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" onsubmit="return confirm('Xoá redirect này?');" class="inline">
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

    <div class="mt-4">
        {{ $redirects->links() }}
    </div>
@endsection
