<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>Cài đặt {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center bg-muted px-4 py-8">
    <div class="w-full max-w-lg">
        <x-card class="p-10">
            <div class="text-center mb-6">
                <h1 class="font-heading text-xl font-extrabold">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">Trình cài đặt hệ thống</p>
            </div>

            <div class="flex justify-center gap-2 mb-7">
                @foreach ([1, 2, 3, 4] as $s)
                    <span class="h-1 w-8 rounded-full {{ $step > $s ? 'bg-brand-green' : ($step == $s ? 'bg-primary' : 'bg-border') }}"></span>
                @endforeach
            </div>

            @yield('content')
        </x-card>
    </div>
</body>
</html>
