@props(['author'])

{{-- Hộp tác giả cuối bài. Chỉ in ra những trường tác giả THẬT SỰ đã điền
     trong hồ sơ (chức danh, tiểu sử) — không tự chế "chuyên gia 10 năm
     kinh nghiệm". Liên kết sang trang tác giả để bài viết không còn là ngõ
     cụt, đồng thời làm rõ tác quyền theo đúng hướng dẫn của Google. --}}
<aside class="mt-16 pt-10 border-t border-border flex flex-col sm:flex-row gap-5">
    <x-avatar :name="$author->name" class="h-14 w-14 text-lg shrink-0" />

    <div class="min-w-0">
        <p class="eyebrow mb-1.5">Người viết</p>
        <p class="font-heading text-xl font-medium">
            <a href="{{ $author->url() }}" class="hover:text-primary transition-colors">{{ $author->name }}</a>
        </p>

        @if ($author->job_title)
            <p class="mt-1 text-sm text-muted-foreground">{{ $author->job_title }}</p>
        @endif

        @if ($author->bio)
            <p class="mt-3.5 text-sm text-muted-foreground leading-relaxed max-w-xl">{{ $author->bio }}</p>
        @endif

        <a href="{{ $author->url() }}" class="group mt-4 inline-flex items-center gap-1.5 font-sans text-sm font-medium text-primary">
            Xem tất cả nội dung <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
        </a>
    </div>
</aside>
