<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
@php
    // Sensible, honest crawl-hint defaults per content type — not measured
    // data, just the standard convention (homepage highest priority/most
    // frequently revisited; evergreen pages like Trang lowest).
    $priorities = ['post' => '0.7', 'service' => '0.7', 'product' => '0.6', 'project' => '0.6', 'page' => '0.5'];
    $changefreqs = ['post' => 'weekly', 'service' => 'monthly', 'product' => 'monthly', 'project' => 'monthly', 'page' => 'monthly'];
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc>{{ route('home') }}</loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>
    {{-- Các trang danh sách cố định: trước đây sitemap chỉ liệt kê bài viết
         đơn lẻ, nên /blog, /dich-vu, /du-an, /san-pham, /lien-he không hề
         xuất hiện — Google phải tự dò ra bằng liên kết nội bộ. --}}
    @foreach ($archives as $archive)
    <url>
        <loc>{{ $archive }}</loc>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>
    @endforeach
    {{-- Archive theo chủ đề — chỉ term thật sự có bài đã xuất bản. --}}
    @foreach ($terms as $term)
    <url>
        <loc>{{ $term['url'] }}</loc>
        <priority>0.5</priority>
        <changefreq>weekly</changefreq>
    </url>
    @endforeach
    @foreach ($posts as $post)
    <url>
        <loc>{{ route('post.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
        <priority>{{ $priorities[$post->post_type] ?? '0.5' }}</priority>
        <changefreq>{{ $changefreqs[$post->post_type] ?? 'monthly' }}</changefreq>
        @if ($post->featured_image)
        <image:image>
            <image:loc>{{ $post->featured_image }}</image:loc>
            <image:title>{{ $post->title }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach
</urlset>
