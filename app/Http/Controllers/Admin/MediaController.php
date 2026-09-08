<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\User;
use App\Support\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Real MIME allowlist (checked against the file's actual detected
     * content via Laravel's `mimetypes:` rule, not the client-supplied
     * extension) — broadened from "images only" per the library redesign,
     * but still a real security boundary: no executables/scripts, ever.
     */
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/quicktime',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/csv',
        'text/plain',
    ];

    private const KIND_TABS = ['image', 'video', 'pdf', 'file'];

    public function index(Request $request)
    {
        $kind = $request->query('type', 'image');
        abort_unless(in_array($kind, self::KIND_TABS, true), 404);

        // "Video" covers both real uploads and embedded links (YouTube/
        // Vimeo) — two sources, one tab, since to the user they're both
        // just "video content".
        $kindsForTab = $kind === 'video' ? ['video', 'embed'] : [$kind];

        return view('admin.media.index', [
            'media' => Media::whereIn('kind', $kindsForTab)->latest()->paginate(24)->withQueryString(),
            'kind' => $kind,
            'counts' => Media::selectRaw('kind, count(*) as c')->groupBy('kind')->pluck('c', 'kind'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Lightweight JSON list for the content-editor media picker (see
     * admin/content/form.blade.php) — images only (featured image / inline
     * content images), not the full library.
     */
    public function picker()
    {
        return response()->json(
            Media::where('kind', 'image')->latest()->limit(60)->get()->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url(),
                'thumb' => $m->thumbnailUrl(),
                'name' => $m->original_name,
            ])
        );
    }

    public function store(Request $request, ImageProcessor $images)
    {
        // The Media Library page supports selecting/dropping several files
        // at once (name="files[]") so a batch doesn't need one submit per
        // file. TinyMCE's own upload handler and the content-form picker's
        // "upload new" form still post a single `file` — kept working as-is.
        if ($request->hasFile('files')) {
            $request->validate([
                'files' => ['required', 'array'],
                'files.*' => ['required', 'file', 'mimetypes:'.implode(',', self::ALLOWED_MIMES)],
            ]);

            foreach ($request->file('files') as $file) {
                $this->storeOne($file, $images, $request->user()->id);
            }

            return back()->with('status', 'Đã tải '.count($request->file('files')).' tệp lên.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', self::ALLOWED_MIMES)],
        ]);

        $media = $this->storeOne($request->file('file'), $images, $request->user()->id);

        // TinyMCE's image upload handler (admin/content/form.blade.php)
        // expects { location: url } back from an XHR/fetch call.
        if ($request->wantsJson()) {
            return response()->json(['location' => $media->url()]);
        }

        return back()->with('status', 'Đã tải tệp lên.');
    }

    private function storeOne(UploadedFile $file, ImageProcessor $images, int $userId): Media
    {
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $kind = Media::kindForMime($mime);

        if ($kind === 'image' && $mime !== 'image/svg+xml') {
            $stored = $images->store($file);
        } else {
            $stored = [
                'path' => $file->store('media', 'public'),
                'thumbnail_path' => null,
                'mime_type' => $mime,
                'width' => null,
                'height' => null,
            ];
        }

        return Media::create([
            'disk' => 'public',
            'kind' => $kind,
            'path' => $stored['path'],
            'thumbnail_path' => $stored['thumbnail_path'],
            'mime_type' => $stored['mime_type'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Adds a YouTube/Vimeo link as a video entry — no file of ours involved.
     * Title + thumbnail come from the provider's own public oEmbed endpoint
     * (a real live lookup, not guessed) and are simply left blank if that
     * call fails, rather than the embed being rejected outright.
     */
    public function storeEmbed(Request $request)
    {
        $data = $request->validate([
            'embed_url' => ['required', 'url', 'max:500'],
        ]);

        $parsed = $this->parseEmbedUrl($data['embed_url']);
        abort_unless($parsed, 422, 'Link video không được hỗ trợ — chỉ hỗ trợ YouTube và Vimeo.');

        $meta = $this->fetchEmbedMeta($data['embed_url'], $parsed['provider']);

        Media::create([
            'disk' => 'public',
            'kind' => 'embed',
            'embed_url' => $parsed['embed_url'],
            'embed_provider' => $parsed['provider'],
            'embed_thumbnail_url' => $meta['thumbnail_url'] ?? $parsed['thumbnail_url'] ?? null,
            'original_name' => $meta['title'] ?? $data['embed_url'],
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Đã thêm video nhúng.');
    }

    /**
     * @return array{provider: string, embed_url: string, thumbnail_url: ?string}|null
     */
    private function parseEmbedUrl(string $url): ?array
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return [
                'provider' => 'youtube',
                'embed_url' => "https://www.youtube.com/embed/{$m[1]}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$m[1]}/hqdefault.jpg",
            ];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return [
                'provider' => 'vimeo',
                'embed_url' => "https://player.vimeo.com/video/{$m[1]}",
                'thumbnail_url' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{title: ?string, thumbnail_url: ?string}
     */
    private function fetchEmbedMeta(string $url, string $provider): array
    {
        $oembedUrl = match ($provider) {
            'youtube' => 'https://www.youtube.com/oembed?format=json&url='.urlencode($url),
            'vimeo' => 'https://vimeo.com/api/oembed.json?url='.urlencode($url),
            default => null,
        };

        if (! $oembedUrl) {
            return ['title' => null, 'thumbnail_url' => null];
        }

        try {
            $response = Http::timeout(5)->get($oembedUrl);

            if ($response->successful()) {
                return [
                    'title' => $response->json('title'),
                    'thumbnail_url' => $response->json('thumbnail_url'),
                ];
            }
        } catch (\Throwable) {
            // Embed still gets added even if the provider is unreachable —
            // just without a fetched title/thumbnail preview.
        }

        return ['title' => null, 'thumbnail_url' => null];
    }

    public function update(Request $request, Media $media)
    {
        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:150'],
            'uploaded_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $media->alt_text = $data['alt_text'] ?? null;

        if (filled($data['title'] ?? null)) {
            $media->original_name = $data['title'];
        }

        if (filled($data['uploaded_by'] ?? null)) {
            $media->uploaded_by = $data['uploaded_by'];
        }

        $media->save();

        // The slug field always resubmits the current slug alongside every
        // other field (uploader, title, alt text), even when the user
        // never touched it — and Str::slug() lowercases, so an original
        // Laravel hash filename (mixed-case) would look "changed" on every
        // single save and get needlessly renamed. Only rename when the
        // submitted value is a real, deliberate change from the current one.
        if (filled($data['slug'] ?? null) && $media->path && Str::slug($data['slug']) !== Str::slug((string) $media->slug())) {
            $media->renameTo($data['slug']);
        }

        if ($request->wantsJson()) {
            $media->refresh();

            return response()->json([
                'original_name' => $media->original_name,
                'slug' => $media->slug(),
                'url' => $media->url(),
                'thumbnail_url' => $media->thumbnailUrl(),
                'uploader_id' => $media->uploaded_by,
                'uploader_name' => $media->uploader?->name ?? '—',
                'updated_at' => $media->updated_at->format('d/m/Y H:i'),
                'used_in' => $media->usedIn(),
            ]);
        }

        return back()->with('status', 'Đã lưu.');
    }

    public function destroy(Media $media)
    {
        $this->deleteMedia($media);

        return back()->with('status', 'Đã xoá.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = Media::whereIn('id', $data['ids'])->get();

        foreach ($items as $media) {
            $this->deleteMedia($media);
        }

        if ($request->wantsJson()) {
            return response()->json(['deleted' => $items->count()]);
        }

        return back()->with('status', "Đã xoá {$items->count()} mục.");
    }

    private function deleteMedia(Media $media): void
    {
        if (! $media->isEmbed()) {
            Storage::disk($media->disk)->delete(array_filter([$media->path, $media->thumbnail_path]));
        }

        $media->delete();
    }
}
