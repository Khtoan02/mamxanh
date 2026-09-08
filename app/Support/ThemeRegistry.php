<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Theme Hierarchy Engine + Child Theme Support (SRS: "THEME / FRONTEND
 * LAYER" — Theme Hierarchy Engine, Child Theme Support).
 *
 * Themes are discovered from TWO directories, in this order:
 *   1. content/themes/   — theme của người cài đặt
 *   2. resources/themes/ — theme đi kèm bản phát hành (lõi)
 *
 * Tách làm hai là điều kiện bắt buộc để CMS có thể phát hành như một sản
 * phẩm cập nhật được: bản cập nhật thay toàn bộ file lõi, nên bất cứ thứ gì
 * người dùng tự tạo mà nằm trong resources/ đều bị xoá sạch. Đây đúng là
 * vấn đề mà wp-content/ của WordPress sinh ra để giải quyết.
 *
 * Trùng slug thì content/themes THẮNG, nhờ vậy người dùng tạo được
 * content/themes/default/ để đè theme mặc định mà không sợ mất khi cập nhật.
 *
 * Views are
 * resolved through Laravel's `theme::` view namespace, registered (in
 * AppServiceProvider) with an ordered path list: active theme -> its
 * ancestor chain -> the `default` theme as the ultimate fallback. Laravel's
 * view finder already tries each namespace path in order, so a child theme
 * only needs to override the templates it actually changes.
 */
class ThemeRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $themes = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $this->themes = [];

        // Quét lõi trước, thư mục người dùng sau — vòng lặp sau ghi đè vòng
        // trước, nên theme của người dùng luôn thắng khi trùng slug.
        foreach ([resource_path('themes/*'), base_path('content/themes/*')] as $pattern) {
            foreach (glob($pattern, GLOB_ONLYDIR) ?: [] as $dir) {
                $manifestPath = $dir.'/theme.json';

                if (! file_exists($manifestPath)) {
                    continue;
                }

                $manifest = json_decode((string) file_get_contents($manifestPath), true);

                if (! is_array($manifest) || empty($manifest['slug'])) {
                    continue;
                }

                $manifest['path'] = $dir;
                $manifest['is_core'] = str_starts_with($dir, resource_path('themes'));
                $this->themes[$manifest['slug']] = $manifest;
            }
        }

        return $this->themes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function active(): array
    {
        $slug = config('theme.active', 'default');

        return $this->get($slug) ?? $this->get('default') ?? [];
    }

    /**
     * Active theme's directory, then each ancestor's, then `default` as the
     * final fallback (deduplicated) — this is the "hierarchy" the theme::
     * namespace resolves views against.
     *
     * @return array<int, string>
     */
    public function resolutionPaths(): array
    {
        $paths = [];
        $seen = [];
        $theme = $this->active();

        while (! empty($theme) && ! isset($seen[$theme['slug']])) {
            $seen[$theme['slug']] = true;
            $paths[] = $theme['path'];
            $theme = ! empty($theme['parent']) ? $this->get($theme['parent']) : null;
        }

        $default = $this->get('default');

        if ($default && ! isset($seen[$default['slug']])) {
            $paths[] = $default['path'];
        }

        return $paths;
    }
}
