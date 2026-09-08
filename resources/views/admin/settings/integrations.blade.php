@extends('layouts.admin')

@section('title', 'Cài đặt — Tích hợp')

@section('content')
    <h2 class="font-heading text-lg font-bold mb-6">Cài đặt</h2>

    @include('admin.settings._tabs', ['active' => 'integrations'])

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.settings.integrations.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">Tích hợp bên thứ 3 — chèn script tuỳ ý</h2>
            <p class="text-xs text-muted-foreground">
                Dán nguyên đoạn mã (Google Analytics, Google Search Console, Facebook Pixel, chat widget, hoặc bất kỳ script nào khác) — không giới hạn ở Google, dán bao nhiêu đoạn tuỳ ý miễn nằm trong 1 trong 2 ô dưới đây. Áp dụng ngay cho toàn bộ trang public sau khi lưu, không hiện trong trang quản trị.
            </p>
            <div>
                <x-label for="custom_head_scripts">Script chèn vào &lt;head&gt;</x-label>
                <textarea id="custom_head_scripts" name="custom_head_scripts" rows="6" placeholder="&lt;script&gt;...&lt;/script&gt; hoặc &lt;meta .../&gt;" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm font-mono text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('custom_head_scripts', $settings['custom_head_scripts']) }}</textarea>
            </div>
            <div>
                <x-label for="custom_footer_scripts">Script chèn trước &lt;/body&gt;</x-label>
                <textarea id="custom_footer_scripts" name="custom_footer_scripts" rows="6" placeholder="&lt;script&gt;...&lt;/script&gt;" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm font-mono text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('custom_footer_scripts', $settings['custom_footer_scripts']) }}</textarea>
            </div>
        </x-card>

        <x-card class="p-6 space-y-4">
            <h2 class="font-heading text-sm font-bold">WooCommerce</h2>
            <p class="text-xs text-muted-foreground">
                Kết nối 1 site WooCommerce (WordPress) để đồng bộ Sản phẩm — hệ thống này là nguồn chính, tạo/sửa sản phẩm ở đây sẽ tự đẩy sang WooCommerce. Lấy Consumer Key/Secret tại WooCommerce → Settings → Advanced → REST API trên site WooCommerce của bạn.
            </p>
            <div>
                <x-label for="woocommerce_url">URL site WooCommerce</x-label>
                <x-input type="url" id="woocommerce_url" name="woocommerce_url" value="{{ old('woocommerce_url', $settings['woocommerce_url']) }}" placeholder="https://cuahang-cua-ban.com" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-label for="woocommerce_consumer_key">Consumer Key</x-label>
                    <x-input type="text" id="woocommerce_consumer_key" name="woocommerce_consumer_key" value="{{ old('woocommerce_consumer_key', $settings['woocommerce_consumer_key']) }}" placeholder="ck_..." />
                </div>
                <div>
                    <x-label for="woocommerce_consumer_secret">Consumer Secret</x-label>
                    <x-input type="password" id="woocommerce_consumer_secret" name="woocommerce_consumer_secret" value="{{ old('woocommerce_consumer_secret', $settings['woocommerce_consumer_secret']) }}" placeholder="cs_..." />
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="woocommerce_sync_enabled" value="0">
                <input type="checkbox" name="woocommerce_sync_enabled" value="1" @checked(old('woocommerce_sync_enabled', $settings['woocommerce_sync_enabled'])) class="rounded border-input">
                Tự động đồng bộ sản phẩm sang WooCommerce khi lưu
            </label>
        </x-card>

        <x-button type="submit" variant="primary" class="!w-auto px-6">Lưu cài đặt</x-button>
    </form>
@endsection
