@extends('layouts.admin')

@section('title', 'Giao diện')

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <div class="grid sm:grid-cols-2 gap-5">
        @foreach ($themes as $theme)
            <x-card class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-heading text-base font-bold">{{ $theme['name'] }}</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $theme['slug'] }}
                            @if (! empty($theme['parent']))
                                · kế thừa từ <span class="font-medium">{{ $theme['parent'] }}</span>
                            @endif
                        </p>
                    </div>
                    @if ($theme['slug'] === $active)
                        <x-badge variant="green">Đang dùng</x-badge>
                    @endif
                </div>

                @if (! empty($theme['description']))
                    <p class="mt-3 text-sm text-muted-foreground leading-relaxed">{{ $theme['description'] }}</p>
                @endif

                @if ($theme['slug'] !== $active)
                    <form method="POST" action="{{ route('admin.themes.activate', $theme['slug']) }}" class="mt-4">
                        @csrf
                        <x-button type="submit" variant="outline" class="!w-auto px-4">Kích hoạt</x-button>
                    </form>
                @endif
            </x-card>
        @endforeach
    </div>
@endsection
