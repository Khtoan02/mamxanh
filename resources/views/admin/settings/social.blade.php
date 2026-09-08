@extends('layouts.admin')

@section('title', 'Cài đặt — Mạng xã hội')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'social'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.social.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Mạng xã hội</h2>
            <p class="text-xs text-muted-foreground">Để trống thì icon tương ứng sẽ không hiện ở footer trang public.</p>

            <div>
                <x-label for="facebook_url">Facebook</x-label>
                <x-input type="url" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url']) }}" placeholder="https://facebook.com/trang-cua-ban" />
            </div>
            <div>
                <x-label for="zalo_url">Zalo</x-label>
                <x-input type="url" id="zalo_url" name="zalo_url" value="{{ old('zalo_url', $settings['zalo_url']) }}" placeholder="https://zalo.me/..." />
            </div>
            <div>
                <x-label for="instagram_url">Instagram</x-label>
                <x-input type="url" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url']) }}" placeholder="https://instagram.com/tai-khoan-cua-ban" />
            </div>
            <div>
                <x-label for="tiktok_url">TikTok</x-label>
                <x-input type="url" id="tiktok_url" name="tiktok_url" value="{{ old('tiktok_url', $settings['tiktok_url']) }}" placeholder="https://tiktok.com/@tai-khoan-cua-ban" />
            </div>
            <div>
                <x-label for="youtube_url">YouTube</x-label>
                <x-input type="url" id="youtube_url" name="youtube_url" value="{{ old('youtube_url', $settings['youtube_url']) }}" placeholder="https://youtube.com/@kenh-cua-ban" />
            </div>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
