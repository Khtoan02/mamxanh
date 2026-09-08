@extends('layouts.admin')

@section('title', 'Cài đặt — Liên hệ')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'contact'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.contact.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Thông tin liên hệ (hiển thị ở footer)</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="contact_email">Email liên hệ</x-label>
                    <x-input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" placeholder="lien-he@ten-mien-cua-ban.vn" />
                </div>
                <div>
                    <x-label for="contact_phone">Điện thoại</x-label>
                    <x-input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}" placeholder="0901 234 567" />
                </div>
            </div>
            <div>
                <x-label for="address">Địa chỉ</x-label>
                <x-input type="text" id="address" name="address" value="{{ old('address', $settings['address']) }}" placeholder="123 Đường ABC, Quận 1, TP.HCM" />
            </div>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
