<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title>{{ config('app.name') }} — Sản phẩm</title>
        <link>{{ url('/') }}</link>
        <description>Feed sản phẩm cho Google Merchant Center</description>
        @foreach ($products as $post)
        <item>
            <g:id>{{ $post->id }}</g:id>
            <title>{{ $post->title }}</title>
            <description>{{ $post->seoDescription() }}</description>
            <link>{{ route('post.show', $post->slug) }}</link>
            <g:image_link>{{ $post->featured_image }}</g:image_link>
            {{-- Hệ thống chưa có quản lý tồn kho thật — mặc định "còn hàng"
                 cho mọi sản phẩm đã publish, không phải số đo thật. --}}
            <g:availability>in stock</g:availability>
            <g:price>{{ $post->priceAmount() }} VND</g:price>
            <g:condition>new</g:condition>
            {{-- Không có brand/GTIN/MPN thật trong hệ thống — khai báo đúng
                 chuẩn Google thay vì bịa mã sản phẩm giả. --}}
            <g:identifier_exists>no</g:identifier_exists>
        </item>
        @endforeach
    </channel>
</rss>
