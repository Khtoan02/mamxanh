@extends('layouts.admin')

@section('title', 'CRM — Liên hệ')

@php
    $statusColor = [
        'new' => 'default',
        'contacted' => 'secondary',
        'won' => 'green',
        'lost' => 'secondary',
    ];
    $tabs = ['all' => 'Tất cả'] + \App\Models\ContactMessage::STATUS_LABELS;
@endphp

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach (\App\Models\ContactMessage::STATUS_LABELS as $key => $label)
            <x-card class="p-4">
                <p class="text-2xl font-heading font-extrabold">{{ $statusCounts[$key] ?? 0 }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $label }}</p>
            </x-card>
        @endforeach
    </div>

    <div class="flex gap-1 rounded-lg border border-border bg-muted p-1 mb-5 w-fit">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.contacts.index', $key === 'all' ? [] : ['status' => $key]) }}"
               class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $status === $key ? 'bg-card shadow-sm text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
            </a>
        @endforeach
    </div>

    @if ($messages->isEmpty())
        <x-card class="p-10 text-center text-sm text-muted-foreground">
            Chưa có liên hệ nào{{ $status !== 'all' ? ' ở trạng thái này' : '' }}.
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($messages as $message)
                <x-card class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-sm">{{ $message->name }}</p>
                            <p class="text-xs text-muted-foreground">{{ $message->email }} @if ($message->phone) · {{ $message->phone }} @endif</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-badge :variant="$statusColor[$message->status] ?? 'default'">{{ $message->statusLabel() }}</x-badge>
                            <span class="text-xs text-muted-foreground whitespace-nowrap">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed whitespace-pre-line">{{ $message->message }}</p>

                    <form method="POST" action="{{ route('admin.contacts.update-status', $message) }}" class="mt-4 pt-4 border-t border-border grid sm:grid-cols-[160px_1fr_auto] gap-3 items-start">
                        @csrf
                        @method('PATCH')
                        <select name="status" onchange="this.form.requestSubmit()" class="rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                            @foreach (\App\Models\ContactMessage::STATUS_LABELS as $key => $label)
                                <option value="{{ $key }}" @selected($message->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="notes" rows="1" placeholder="Ghi chú nội bộ…" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ $message->notes }}</textarea>
                        <button type="submit" class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-accent">Lưu ghi chú</button>
                    </form>

                    <form method="POST" action="{{ route('admin.contacts.destroy', $message) }}" class="mt-2" onsubmit="return confirm('Xoá liên hệ này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-destructive hover:underline">Xoá</button>
                    </form>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $messages->links() }}
        </div>
    @endif
@endsection
