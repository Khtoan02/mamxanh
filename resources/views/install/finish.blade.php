@extends('install.layout', ['step' => 4])

@section('content')
    <div class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-green/10 text-2xl text-brand-green">
            &#10003;
        </div>
        <h2 class="font-heading text-lg font-bold">Cài đặt hoàn tất!</h2>
        <p class="mt-2 mb-6 text-sm text-muted-foreground">{{ config('app.name') }} đã sẵn sàng sử dụng. File install.lock đã được tạo để bảo vệ trình cài đặt.</p>
        <a href="{{ url('/') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors bg-primary text-primary-foreground hover:opacity-90">
            Đi tới trang chủ
        </a>
    </div>
@endsection
