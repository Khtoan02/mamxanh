<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\GoogleProductFeed;
use App\Support\SeoTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The site's central SEO/GEO hub — everything that used to be scattered
 * under Settings > "SEO kỹ thuật" (robots.txt, IndexNow) plus the new
 * Titles & Meta template system and sitemap post-type toggles, all under
 * one `/admin/seo/*` nav item instead of buried in Settings.
 */
class SeoController extends Controller
{
    /** @var array<string, string> content types the Titles & Meta editor covers */
    public const TITLE_TEMPLATE_TYPES = [
        'post' => 'Bài viết',
        'page' => 'Trang',
        'product' => 'Sản phẩm',
        'service' => 'Dịch vụ',
        'project' => 'Dự án',
    ];

    public function titles()
    {
        $templates = [];
        foreach (self::TITLE_TEMPLATE_TYPES as $type => $label) {
            $templates[$type] = Setting::get('seo_title_'.$type, '%title% %sep% %sitename%');
        }

        return view('admin.seo.titles', [
            'types' => self::TITLE_TEMPLATE_TYPES,
            'templates' => $templates,
            'sep' => Setting::get('seo_sep', ' — '),
            'titleArchive' => Setting::get('seo_title_archive', '%term% %sep% %sitename%'),
            'descArchive' => Setting::get('seo_desc_archive', '%term% %sep% %sitename%'),
            'titleHome' => Setting::get('seo_title_home', '%sitename% %sep% %tagline%'),
            'descHome' => Setting::get('seo_desc_home', '%tagline%'),
        ]);
    }

    public function updateTitles(Request $request)
    {
        $data = $request->validate([
            'seo_sep' => ['nullable', 'string', 'max:20'],
            'seo_title_archive' => ['nullable', 'string', 'max:255'],
            'seo_desc_archive' => ['nullable', 'string', 'max:255'],
            'seo_title_home' => ['nullable', 'string', 'max:255'],
            'seo_desc_home' => ['nullable', 'string', 'max:255'],
            ...collect(self::TITLE_TEMPLATE_TYPES)->keys()
                ->mapWithKeys(fn ($type) => ['seo_title_'.$type => ['nullable', 'string', 'max:255']])
                ->all(),
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?: null);
        }

        return redirect()->route('admin.seo.titles')->with('status', 'Đã lưu.');
    }

    public function sitemap()
    {
        return view('admin.seo.sitemap', [
            'postTypes' => get_post_types(),
            'excluded' => json_decode(Setting::get('sitemap_excluded_types', '[]'), true) ?: [],
        ]);
    }

    public function updateSitemap(Request $request)
    {
        $data = $request->validate([
            'included' => ['nullable', 'array'],
            'included.*' => ['string'],
        ]);

        $included = $data['included'] ?? [];
        $excluded = array_values(array_diff(array_keys(get_post_types()), $included));

        Setting::set('sitemap_excluded_types', json_encode($excluded));

        return redirect()->route('admin.seo.sitemap')->with('status', 'Đã lưu.');
    }

    public function productFeed()
    {
        return view('admin.seo.product-feed', [
            'feedUrl' => route('product-feed'),
            'stats' => GoogleProductFeed::stats(),
        ]);
    }

    public function robots()
    {
        return view('admin.seo.robots', [
            'robotsTxt' => Setting::get('robots_txt', $this->defaultRobotsTxt()),
        ]);
    }

    public function updateRobots(Request $request)
    {
        $data = $request->validate([
            'robots_txt' => ['nullable', 'string', 'max:5000'],
        ]);

        Setting::set('robots_txt', $data['robots_txt'] ?? $this->defaultRobotsTxt());

        return redirect()->route('admin.seo.robots')->with('status', 'Đã lưu.');
    }

    public function defaultRobotsTxt(): string
    {
        return "User-agent: *\nDisallow: /admin\nDisallow: /login\nDisallow: /install\n\nSitemap: ".url('/sitemap.xml');
    }

    public function indexnow()
    {
        return view('admin.seo.indexnow', [
            'enabled' => Setting::get('indexnow_enabled', ''),
            'indexNowKey' => $this->indexNowKey(),
        ]);
    }

    public function updateIndexnow(Request $request)
    {
        $data = $request->validate([
            'indexnow_enabled' => ['nullable', 'boolean'],
        ]);

        Setting::set('indexnow_enabled', ($data['indexnow_enabled'] ?? false) ? '1' : '');

        return redirect()->route('admin.seo.indexnow')->with('status', 'Đã lưu.');
    }

    /**
     * Generated once and persisted — proves domain ownership to IndexNow by
     * being served back at /{key}.txt (see routes/web.php), the protocol's
     * own verification mechanism, no external registration needed.
     */
    private function indexNowKey(): string
    {
        $key = Setting::get('indexnow_key');

        if (! $key) {
            $key = Str::random(32);
            Setting::set('indexnow_key', $key);
        }

        return $key;
    }
}
