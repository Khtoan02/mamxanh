<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ config('app.name') }}</title>
        <link>{{ route('blog.index') }}</link>
        <description>{{ env('SITE_TAGLINE', config('app.name')) }}</description>
        <language>vi</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
        @if ($posts->isNotEmpty())
        <lastBuildDate>{{ $posts->first()->published_at?->toRfc2822String() }}</lastBuildDate>
        @endif
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('post.show', $post->slug) }}</link>
            {{-- guid phải BỀN theo thời gian: nếu dùng URL và slug đổi, mọi
                 trình đọc RSS sẽ coi bài cũ là bài mới và báo trùng. --}}
            <guid isPermaLink="false">{{ url('/') }}?p={{ $post->id }}</guid>
            <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
            <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">{{ $post->author->name }}</dc:creator>
            <description>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 300) }}</description>
        </item>
        @endforeach
    </channel>
</rss>
