@extends('layouts.admin')

@section('title', 'Cài đặt — Chung')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'general'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <x-media-picker-modal />

    <form method="POST" action="{{ route('admin.settings.general.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <div>
                <x-label for="site_title">Tên website</x-label>
                <x-input type="text" id="site_title" name="site_title" value="{{ old('site_title', $settings['site_title']) }}" required />
            </div>

            <div>
                <x-label for="site_tagline">Khẩu hiệu (tagline)</x-label>
                <x-input type="text" id="site_tagline" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="locale">Ngôn ngữ mặc định</x-label>
                    <select id="locale" name="locale" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="vi" @selected(old('locale', $settings['locale']) === 'vi')>Tiếng Việt</option>
                        <option value="en" @selected(old('locale', $settings['locale']) === 'en')>English</option>
                    </select>
                </div>
                <div>
                    <x-label for="timezone">Múi giờ</x-label>
                    <select id="timezone" name="timezone" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="Asia/Ho_Chi_Minh" @selected(old('timezone', $settings['timezone']) === 'Asia/Ho_Chi_Minh')>Asia/Ho_Chi_Minh</option>
                        <option value="UTC" @selected(old('timezone', $settings['timezone']) === 'UTC')>UTC</option>
                    </select>
                </div>
            </div>
        </x-card>

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Thương hiệu</h2>
            <x-media-picker-field name="site_logo" label="Logo" :value="$settings['site_logo']" hint="Hiện ở header/footer trang public. Để trống thì dùng biểu tượng mặc định." />
            <x-media-picker-field name="site_favicon" label="Favicon" :value="$settings['site_favicon']" hint="Icon hiện trên tab trình duyệt. Nên dùng ảnh vuông, tối thiểu 32×32px." />
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
