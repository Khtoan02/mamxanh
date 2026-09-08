@extends('install.layout', ['step' => 1])

@section('content')
    <h2 class="font-heading text-lg font-bold">Bước 1: Kiểm tra hệ thống</h2>
    <p class="mt-1 mb-5 text-sm text-muted-foreground">Kiểm tra máy chủ có đủ điều kiện để cài đặt {{ config('app.name') }} không.</p>

    <ul class="divide-y divide-border mb-5">
        @foreach ($checks as $check)
            <li class="flex items-center gap-3 py-2.5 text-sm">
                <x-badge :variant="$check['passed'] ? 'green' : 'default'" class="{{ $check['passed'] ? '' : 'bg-destructive/10 text-destructive' }}">
                    {{ $check['passed'] ? 'OK' : 'FAIL' }}
                </x-badge>
                {{ $check['label'] }}
                <span class="ml-auto text-xs text-muted-foreground">{{ $check['detail'] }}</span>
            </li>
        @endforeach
    </ul>

    @if (! $allPassed)
        <x-alert variant="destructive" class="mb-5">
            Máy chủ chưa đáp ứng đủ điều kiện. Vui lòng cài/bật các extension hoặc cấp quyền ghi còn thiếu ở trên rồi tải lại trang.
        </x-alert>
    @endif

    <a href="{{ route('install.database') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90 {{ ! $allPassed ? 'pointer-events-none opacity-50' : '' }}">
        Tiếp tục
    </a>
@endsection
