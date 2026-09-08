<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Image Processing Engine (SRS section IV.3.b): stores the upload via
 * Laravel's Storage facade (Flysystem under the hood — swapping `disk`
 * from 'public' to an 's3' disk in config/filesystems.php is the entire
 * migration path, no code change needed here).
 *
 * JPEG/PNG uploads are re-encoded to WebP on the way in (real GD
 * `imagewebp()` conversion — confirmed available on this server; AVIF is
 * NOT supported by this PHP/GD build, so it's deliberately not offered
 * rather than pretending to). GIF/SVG/already-WebP are stored untouched:
 * GD's imagewebp() only ever captures a single frame, so converting an
 * animated GIF would silently kill the animation — not worth the size
 * savings. A downscaled WebP thumbnail is generated for every raster
 * image regardless of whether the original itself got converted.
 */
class ImageProcessor
{
    private const THUMBNAIL_WIDTH = 400;

    private const WEBP_QUALITY = 82;

    private const CONVERTIBLE_MIMES = ['image/jpeg', 'image/png'];

    private const THUMBNAILABLE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(private readonly string $disk = 'public')
    {
    }

    /**
     * @return array{path: string, thumbnail_path: ?string, mime_type: string, width: ?int, height: ?int}
     */
    public function store(UploadedFile $file, string $directory = 'media'): array
    {
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        if (in_array($mime, self::CONVERTIBLE_MIMES, true)) {
            $converted = $this->convertToWebp($file, $directory);

            if ($converted) {
                return $converted;
            }
            // Corrupt/undecodable image data — fall through and store the
            // original untouched rather than losing the upload outright.
        }

        $path = $file->store($directory, $this->disk);
        [$width, $height] = $this->realDimensions($file, $mime);

        return [
            'path' => $path,
            'thumbnail_path' => in_array($mime, self::THUMBNAILABLE_MIMES, true)
                ? $this->makeThumbnail($file->getRealPath(), $path)
                : null,
            'mime_type' => $mime,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{path: string, thumbnail_path: ?string, mime_type: string, width: int, height: int}|null
     */
    private function convertToWebp(UploadedFile $file, string $directory): ?array
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return null;
        }

        $this->preserveTransparency($source);

        $width = imagesx($source);
        $height = imagesy($source);

        ob_start();
        imagewebp($source, null, self::WEBP_QUALITY);
        $binary = ob_get_clean();

        $path = rtrim($directory, '/').'/'.pathinfo($file->hashName(), PATHINFO_FILENAME).'.webp';
        Storage::disk($this->disk)->put($path, $binary);

        $thumbnailPath = $this->makeThumbnailFromResource($source, $width, $height, $path);
        imagedestroy($source);

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => 'image/webp',
            'width' => $width,
            'height' => $height,
        ];
    }

    private function makeThumbnail(string $realPath, string $originalPath): ?string
    {
        $source = @imagecreatefromstring((string) file_get_contents($realPath));

        if ($source === false) {
            return null;
        }

        $thumbnailPath = $this->makeThumbnailFromResource($source, imagesx($source), imagesy($source), $originalPath);
        imagedestroy($source);

        return $thumbnailPath;
    }

    /**
     * @param  \GdImage  $source
     */
    private function makeThumbnailFromResource($source, int $width, int $height, string $originalPath): ?string
    {
        if ($width <= self::THUMBNAIL_WIDTH) {
            return null;
        }

        $this->preserveTransparency($source);

        $targetHeight = (int) round($height * (self::THUMBNAIL_WIDTH / $width));
        $thumbnail = imagecreatetruecolor(self::THUMBNAIL_WIDTH, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, self::THUMBNAIL_WIDTH, $targetHeight, $width, $height);

        ob_start();
        imagewebp($thumbnail, null, self::WEBP_QUALITY);
        $binary = ob_get_clean();
        imagedestroy($thumbnail);

        $thumbnailPath = preg_replace('/\.[^.]+$/', '', $originalPath).'-thumb.webp';
        Storage::disk($this->disk)->put($thumbnailPath, $binary);

        return $thumbnailPath;
    }

    /**
     * @param  \GdImage  $image
     */
    private function preserveTransparency($image): void
    {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function realDimensions(UploadedFile $file, string $mime): array
    {
        if (! str_starts_with($mime, 'image/')) {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());

        return $size ? [$size[0], $size[1]] : [null, null];
    }
}
