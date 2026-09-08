@extends('layouts.guest')

@section('title', 'Đăng nhập — ' . config('app.name'))
@section('subtitle', 'Đăng nhập vào trang quản trị')

@section('content')
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-label for="email">Email</x-label>
            <x-input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus />
        </div>

        <div>
            <x-label for="password">Mật khẩu</x-label>
            <x-input type="password" id="password" name="password" required />
        </div>

        <label class="flex items-center gap-2 text-sm text-muted-foreground">
            <input type="checkbox" name="remember" class="rounded border-input">
            Ghi nhớ đăng nhập
        </label>

        <x-button type="submit" variant="primary">Đăng nhập</x-button>
    </form>
@endsection
