@extends('install.layout', ['step' => 3])

@section('content')
    <h2 class="font-heading text-lg font-bold">Bước 3: Thông tin site & tài khoản Admin</h2>
    <p class="mt-1 mb-5 text-sm text-muted-foreground">Thiết lập thông tin website và tài khoản Super Admin đầu tiên.</p>

    @if ($errors->any())
        <x-alert variant="destructive" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('install.site.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-label for="site_title">Site Title</x-label>
            <x-input type="text" id="site_title" name="site_title" value="{{ old('site_title', config('app.name')) }}" required />
        </div>

        <div>
            <x-label for="site_tagline">Tagline</x-label>
            <x-input type="text" id="site_tagline" name="site_tagline" value="{{ old('site_tagline') }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-label for="locale">Ngôn ngữ mặc định</x-label>
                <select id="locale" name="locale" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="vi" @selected(old('locale', 'vi') == 'vi')>Tiếng Việt</option>
                    <option value="en" @selected(old('locale') == 'en')>English</option>
                </select>
            </div>
            <div>
                <x-label for="timezone">Múi giờ</x-label>
                <select id="timezone" name="timezone" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="Asia/Ho_Chi_Minh" @selected(old('timezone', 'Asia/Ho_Chi_Minh') == 'Asia/Ho_Chi_Minh')>Asia/Ho_Chi_Minh</option>
                    <option value="UTC" @selected(old('timezone') == 'UTC')>UTC</option>
                </select>
            </div>
        </div>

        <div>
            <x-label for="admin_name">Admin Name</x-label>
            <x-input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required />
        </div>

        <div>
            <x-label for="admin_email">Admin Email</x-label>
            <x-input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required />
        </div>

        <div>
            <x-label for="admin_password">Admin Password</x-label>
            <x-input type="password" id="admin_password" name="admin_password" required minlength="8" />
        </div>

        <div>
            <x-label for="admin_password_confirmation">Xác nhận Password</x-label>
            <x-input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required minlength="8" />
        </div>

        <x-button type="submit" variant="primary">Hoàn tất cài đặt</x-button>
    </form>
@endsection
