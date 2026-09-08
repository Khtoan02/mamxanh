@extends('layouts.admin')

@section('title', 'Cài đặt — Email')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'email'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.email.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Trình gửi email</h2>
            <p class="text-xs text-muted-foreground">
                "Chỉ ghi log" chỉ dùng để phát triển — email sẽ không thực sự gửi đi. Chọn "SMTP" và điền đủ thông tin bên dưới để gửi email thật (liên hệ mới, sao lưu, thử nghiệm...).
            </p>
            <div>
                <x-label for="mail_mailer">Phương thức gửi</x-label>
                <select id="mail_mailer" name="mail_mailer" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="log" @selected(old('mail_mailer', $settings['mail_mailer']) === 'log')>Chỉ ghi log (không gửi thật)</option>
                    <option value="smtp" @selected(old('mail_mailer', $settings['mail_mailer']) === 'smtp')>SMTP</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="mail_host">SMTP Host</x-label>
                    <x-input type="text" id="mail_host" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}" placeholder="smtp.gmail.com" />
                </div>
                <div>
                    <x-label for="mail_port">Cổng (port)</x-label>
                    <x-input type="number" id="mail_port" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}" placeholder="587" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="mail_username">Tên đăng nhập</x-label>
                    <x-input type="text" id="mail_username" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" autocomplete="off" />
                </div>
                <div>
                    <x-label for="mail_password">Mật khẩu</x-label>
                    <x-input type="password" id="mail_password" name="mail_password" value="" placeholder="{{ $settings['mail_password'] ? '•••••••• (để trống nếu giữ nguyên)' : '' }}" autocomplete="new-password" />
                </div>
            </div>

            <div>
                <x-label for="mail_scheme">Mã hoá</x-label>
                <select id="mail_scheme" name="mail_scheme" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="" @selected(old('mail_scheme', $settings['mail_scheme']) === '')>Không / STARTTLS tự động (khuyên dùng cho port 587)</option>
                    <option value="smtps" @selected(old('mail_scheme', $settings['mail_scheme']) === 'smtps')>SSL/TLS (SMTPS, thường dùng port 465)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-label for="mail_from_address">Email người gửi</x-label>
                    <x-input type="email" id="mail_from_address" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" placeholder="lien-he@ten-mien-cua-ban.vn" />
                </div>
                <div>
                    <x-label for="mail_from_name">Tên người gửi</x-label>
                    <x-input type="text" id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" />
                </div>
            </div>
        </x-card>

        <div class="flex items-center gap-3">
            <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
        </div>
    </form>

    <x-card class="p-6 mt-5 max-w-2xl">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-heading text-sm font-bold">Gửi email thử nghiệm</h2>
                <p class="text-xs text-muted-foreground mt-1">Gửi một email test tới địa chỉ email tài khoản của bạn để xác nhận cấu hình hoạt động thật (nhớ lưu cài đặt ở trên trước).</p>
            </div>
            <form method="POST" action="{{ route('admin.settings.email.test') }}">
                @csrf
                <x-button type="submit" variant="outline" class="!w-auto px-4 shrink-0">Gửi thử</x-button>
            </form>
        </div>
    </x-card>
@endsection
