@extends('theme::layout')

@php
    $breadcrumbs = [
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => $author->name],
    ];
@endphp

@section('title', \App\Support\SeoTemplate::render('%term% %sep% %sitename%', ['term' => $author->name]))
@section('description', $author->bio
    ? \Illuminate\Support\Str::limit(strip_tags($author->bio), 155)
    : $author->name.' — nội dung đã xuất bản trên '.config('app.name'))

@push('schema')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => url()->current(),
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $author->name,
                'jobTitle' => $author->job_title ?: null,
                'description' => $author->bio ?: null,
                'url' => url()->current(),
                'worksFor' => ['@id' => url('/').'#organization'],
            ]),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <div class="pt-10 sm:pt-14">
        <x-breadcrumbs :items="$breadcrumbs" />

        <div class="reveal flex flex-col sm:flex-row sm:items-start gap-6 max-w-3xl">
            <x-avatar :name="$author->name" class="h-16 w-16 text-xl shrink-0" />

            <div>
                <p class="eyebrow mb-2">Tác giả</p>
                <h1 class="font-heading text-3xl sm:text-4xl font-semibold leading-tight tracking-tight">{{ $author->name }}</h1>

                @if ($author->job_title)
                    <p class="mt-2 text-muted-foreground">{{ $author->job_title }}</p>
                @endif

                @if ($author->bio)
                    <p class="mt-5 leading-relaxed text-muted-foreground max-w-2xl">{{ $author->bio }}</p>
                @endif
            </div>
        </div>

        <div class="chapter-rule reveal mt-10 w-20"></div>
    </div>

    <div class="mt-12 pb-20 sm:pb-28">
        <p class="eyebrow mb-8">{{ $posts->total() }} nội dung đã xuất bản</p>

        <div class="reveal-stagger grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
            @foreach ($posts as $post)
                <x-marketing.content-card :post="$post" show-type />
            @endforeach
        </div>

        <div class="mt-16">{{ $posts->links() }}</div>
    </div>
@endsection
