<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\EnvironmentWriter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'disk', 'path', 'thumbnail_path', 'original_name', 'alt_text', 'mime_type', 'size',
    'kind', 'width', 'height', 'embed_url', 'embed_provider', 'embed_thumbnail_url', 'uploaded_by',
])]
class Media extends Model
{
    public const KIND_LABELS = [
        'image' => 'Ảnh',
        'video' => 'Video',
        'pdf' => 'PDF',
        'embed' => 'Video nhúng',
        'file' => 'Tệp',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isEmbed(): bool
    {
        return $this->kind === 'embed';
    }

    /**
     * The real MIME type of an uploaded file — never guessed for an embed
     * (there is no file, only an external URL).
     */
    public static function kindForMime(?string $mime): string
    {
        if (! $mime) {
            return 'file';
        }

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            $mime === 'application/pdf' => 'pdf',
            default => 'file',
        };
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? 'Tệp';
    }

    private const FORMAT_BADGES = [
        'image/jpeg' => 'JPG',
        'image/png' => 'PNG',
        'image/webp' => 'WEBP',
        'image/gif' => 'GIF',
        'image/svg+xml' => 'SVG',
        'image/avif' => 'AVIF',
        'video/mp4' => 'MP4',
        'video/webm' => 'WEBM',
        'video/quicktime' => 'MOV',
        'application/pdf' => 'PDF',
        'application/msword' => 'DOC',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
        'application/vnd.ms-excel' => 'XLS',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
        'application/zip' => 'ZIP',
        'text/csv' => 'CSV',
        'text/plain' => 'TXT',
    ];

    private const EMBED_PROVIDER_BADGES = [
        'youtube' => 'YOUTUBE',
        'vimeo' => 'VIMEO',
    ];

    /**
     * A short real-format label (JPG/PNG/WEBP/MP4/YOUTUBE/...) derived from
     * the actually-stored MIME type or embed provider — never guessed from
     * the original filename, since the real file may have been converted
     * (e.g. an uploaded PNG is re-encoded to WebP, so its badge reads WEBP).
     */
    public function formatBadge(): string
    {
        if ($this->isEmbed()) {
            return self::EMBED_PROVIDER_BADGES[$this->embed_provider] ?? strtoupper((string) $this->embed_provider);
        }

        if ($this->mime_type && isset(self::FORMAT_BADGES[$this->mime_type])) {
            return self::FORMAT_BADGES[$this->mime_type];
        }

        $extension = pathinfo($this->original_name ?? '', PATHINFO_EXTENSION);

        return $extension ? strtoupper($extension) : 'FILE';
    }

    /**
     * Whether this media has a real SEO/accessibility description filled
     * in (`alt_text` — doubles as "alt text" for images and a general
     * description for video/pdf/file/embed). Drives the green/red border
     * in the library grid.
     */
    public function hasSeoInfo(): bool
    {
        return filled($this->alt_text);
    }

    public function url(): ?string
    {
        if ($this->isEmbed()) {
            return $this->embed_url;
        }

        return $this->path ? Storage::disk($this->disk)->url($this->path) : null;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->isEmbed()) {
            return $this->embed_thumbnail_url;
        }

        if ($this->thumbnail_path) {
            return Storage::disk($this->disk)->url($this->thumbnail_path);
        }

        return $this->kind === 'image' ? $this->url() : null;
    }

    public function humanSize(): string
    {
        if (! $this->size) {
            return '—';
        }

        return match (true) {
            $this->size >= 1_048_576 => round($this->size / 1_048_576, 1).' MB',
            default => round($this->size / 1024, 1).' KB',
        };
    }

    public function dimensions(): ?string
    {
        return $this->width && $this->height ? "{$this->width}×{$this->height}" : null;
    }

    /**
     * The current filename portion of the URL, without directory or
     * extension — what the "URL slug" field in the detail modal edits.
     */
    public function slug(): ?string
    {
        return $this->path ? pathinfo($this->path, PATHINFO_FILENAME) : null;
    }

    /**
     * Really renames the file (and its thumbnail, if any) on disk so the
     * URL itself becomes SEO-friendly instead of the random hash Laravel's
     * upload handler names it — then rewrites every real place this file's
     * old URL was actually referenced (post featured images, post content
     * HTML, the site logo/favicon in `.env`) so nothing 404s. Embeds have
     * no file on disk, so this is a no-op for them.
     */
    public function renameTo(string $desiredSlug): bool
    {
        if (! $this->path) {
            return false;
        }

        $slug = Str::slug($desiredSlug);

        if ($slug === '') {
            return false;
        }

        $directory = pathinfo($this->path, PATHINFO_DIRNAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        $candidate = $slug;
        $suffix = 1;

        while (
            "{$directory}/{$candidate}.{$extension}" !== $this->path
            && Storage::disk($this->disk)->exists("{$directory}/{$candidate}.{$extension}")
        ) {
            $suffix++;
            $candidate = "{$slug}-{$suffix}";
        }

        $newPath = "{$directory}/{$candidate}.{$extension}";

        if ($newPath === $this->path) {
            return true;
        }

        $oldUrl = $this->url();
        $oldPath = $this->path;
        $oldThumbnailPath = $this->thumbnail_path;

        Storage::disk($this->disk)->move($this->path, $newPath);

        $newThumbnailPath = null;
        if ($oldThumbnailPath && Storage::disk($this->disk)->exists($oldThumbnailPath)) {
            $newThumbnailPath = preg_replace('/\.[^.]+$/', '', $newPath).'-thumb.webp';
            Storage::disk($this->disk)->move($oldThumbnailPath, $newThumbnailPath);
        }

        $this->path = $newPath;
        $this->thumbnail_path = $newThumbnailPath ?? $this->thumbnail_path;
        $this->save();

        $this->propagateUrlChange($oldUrl, $this->url(), $oldPath, $newPath);

        return true;
    }

    private function propagateUrlChange(?string $oldUrl, ?string $newUrl, string $oldPath, string $newPath): void
    {
        if (! $oldUrl || ! $newUrl || $oldUrl === $newUrl) {
            return;
        }

        // `featured_image` really does store the full absolute URL (see
        // MediaController::picker() / <x-media-picker-field>), but the
        // WYSIWYG editor saves <img> src attributes as paths RELATIVE to
        // the post page (e.g. `../../../storage/media/xyz.jpg`), not the
        // full URL — so content must be patched on the disk-relative path
        // (the part common to both forms), never on the full URL, or the
        // replacement silently matches nothing.
        Post::where('featured_image', $oldUrl)->update(['featured_image' => $newUrl]);

        Post::where('content', 'like', "%{$oldPath}%")->get(['id', 'content'])->each(
            fn (Post $post) => $post->update(['content' => str_replace($oldPath, $newPath, $post->content)])
        );

        if (($logo = env('SITE_LOGO')) && str_contains($logo, $oldPath)) {
            app(EnvironmentWriter::class)->set(['SITE_LOGO' => str_replace($oldPath, $newPath, $logo)]);
        }

        if (($favicon = env('SITE_FAVICON')) && str_contains($favicon, $oldPath)) {
            app(EnvironmentWriter::class)->set(['SITE_FAVICON' => str_replace($oldPath, $newPath, $favicon)]);
        }
    }

    /**
     * Where this file is really referenced — computed by scanning, not a
     * stored relation (there is no media-usage pivot table in this schema).
     * `path` is the disk-relative part of every URL that ever pointed at
     * this file (see `url()`), so matching on it survives an APP_URL change
     * that a full-URL match would miss. Embeds aren't picker-integrated
     * into post content yet, so usage isn't tracked for them.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function usedIn(): array
    {
        if (! $this->path) {
            return [];
        }

        $usages = [];

        Post::where('featured_image', 'like', "%{$this->path}%")->get(['id', 'title'])->each(function (Post $post) use (&$usages) {
            $usages[] = ['label' => "Ảnh đại diện — {$post->title}", 'url' => route('admin.content.edit', $post)];
        });

        Post::where('content', 'like', "%{$this->path}%")->get(['id', 'title'])->each(function (Post $post) use (&$usages) {
            $usages[] = ['label' => "Nội dung bài viết — {$post->title}", 'url' => route('admin.content.edit', $post)];
        });

        if (($logo = env('SITE_LOGO')) && str_contains($logo, $this->path)) {
            $usages[] = ['label' => 'Logo website (Cài đặt chung)', 'url' => route('admin.settings.general')];
        }

        if (($favicon = env('SITE_FAVICON')) && str_contains($favicon, $this->path)) {
            $usages[] = ['label' => 'Favicon website (Cài đặt chung)', 'url' => route('admin.settings.general')];
        }

        return $usages;
    }
}
