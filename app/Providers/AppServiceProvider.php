<?php

namespace App\Providers;

use App\Models\Capability;
use App\Models\SlowQuery;
use App\Models\User;
use App\Support\HookRegistry;
use App\Support\PostTypeRegistry;
use App\Support\TaxonomyRegistry;
use App\Support\ThemeRegistry;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Phải chạy ở register(), TRƯỚC mọi middleware: EncryptCookies cần
        // APP_KEY, nên nếu để tới boot() thì bản cài mới vẫn 500 trước khi
        // tới được trang /install.
        \App\Support\InstallBootstrapper::ensureAppKey();

        $this->app->singleton(HookRegistry::class);
        $this->app->singleton(PostTypeRegistry::class);
        $this->app->singleton(TaxonomyRegistry::class);
        $this->app->singleton(ThemeRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerCapabilityGates();
        $this->registerCoreHooks();

        // Plugin phải nạp SAU khi hook lõi đã đăng ký (để plugin ghi đè được)
        // nhưng TRƯỚC do_action('mxd_init') ở cuối hàm này — đó là thời điểm
        // duy nhất plugin còn kịp đăng ký post type/taxonomy của riêng mình.
        \App\Support\PluginLoader::boot();
        $this->registerThemeNamespace();
        $this->registerSlowQueryLogging();

        // Laravel's bundled pagination views hard-code Tailwind's default
        // grey palette (bg-white / text-gray-500), which renders white text
        // on a white pill in dark mode. Ours uses theme tokens only.
        Paginator::defaultView('vendor.pagination.mxd');
        Paginator::defaultSimpleView('vendor.pagination.mxd');

        do_action('mxd_init');
    }

    /**
     * Website Health's "Truy vấn chậm" check — real query timing captured
     * during actual web requests (console/migrations excluded so schema
     * changes don't get logged as app slow queries). Stores the SQL
     * template only, never bound values, so no form input/search terms
     * ever land in this table.
     */
    private function registerSlowQueryLogging(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            // The str_contains guard prevents a slow insert into this very
            // table from re-triggering itself (infinite recursion) if the
            // DB is under enough load that even this write is slow.
            if ($query->time < 500 || ! file_exists(storage_path('app/install.lock')) || str_contains($query->sql, 'slow_queries')) {
                return;
            }

            try {
                SlowQuery::create([
                    'sql' => mb_substr($query->sql, 0, 2000),
                    'time_ms' => $query->time,
                    'path' => mb_substr(request()->path(), 0, 255),
                ]);
            } catch (\Throwable) {
                // Never let health-metric logging break the actual request.
            }
        });
    }

    /**
     * Maps every row in `capabilities` to a Laravel Gate of the same name,
     * so `@can('edit_posts')` / `$user->can('edit_posts')` work directly.
     * super_admin bypasses every check (see User::hasCapability()).
     * Guarded so it's a no-op before the site is installed (no DB yet).
     */
    private function registerCapabilityGates(): void
    {
        if (! file_exists(storage_path('app/install.lock'))) {
            return;
        }

        try {
            if (! Schema::hasTable('capabilities')) {
                return;
            }

            Gate::before(fn (User $user, string $ability) => $user->role === 'super_admin' ? true : null);

            foreach (Capability::pluck('slug') as $slug) {
                Gate::define($slug, fn (User $user) => $user->hasCapability($slug));
            }
        } catch (\Throwable) {
            // DB unreachable at boot — degrade instead of taking the whole
            // app down; requests that actually touch the DB will fail with
            // their own catchable error at the point of use.
        }
    }

    /**
     * Core hook listeners. Extensions register their own via add_action()/
     * add_filter() — see docs/hooks.md for the catalog.
     */
    private function registerCoreHooks(): void
    {
        add_action('mxd_after_login', function (User $user): void {
            $user->forceFill(['last_login_at' => now()])->save();
        });

        add_action('mxd_init', function (): void {
            register_post_type('post', [
                'label' => 'Bài viết',
                'label_singular' => 'Bài viết',
                'supports' => ['title', 'editor', 'excerpt', 'featured_image'],
            ]);

            register_post_type('page', [
                'label' => 'Trang',
                'label_singular' => 'Trang',
                'supports' => ['title', 'editor', 'featured_image'],
            ]);

            register_post_type('service', [
                'label' => 'Dịch vụ',
                'label_singular' => 'Dịch vụ',
                'supports' => ['title', 'editor', 'excerpt', 'featured_image'],
            ]);

            register_post_type('project', [
                'label' => 'Dự án',
                'label_singular' => 'Dự án',
                'supports' => ['title', 'editor', 'excerpt', 'featured_image'],
            ]);

            register_post_type('product', [
                'label' => 'Sản phẩm',
                'label_singular' => 'Sản phẩm',
                'supports' => ['title', 'editor', 'excerpt', 'featured_image'],
            ]);

            register_taxonomy('category', ['post'], ['label' => 'Danh mục', 'hierarchical' => true]);
            register_taxonomy('post_tag', ['post'], ['label' => 'Thẻ', 'hierarchical' => false]);
            register_taxonomy('product_category', ['product'], ['label' => 'Loại sản phẩm', 'hierarchical' => true]);
            register_taxonomy('product_tag', ['product'], ['label' => 'Thẻ sản phẩm', 'hierarchical' => false]);
        });
    }

    /**
     * Maps the `theme::` view namespace to the active theme's directory,
     * its ancestor chain, then `default` — see App\Support\ThemeRegistry.
     */
    private function registerThemeNamespace(): void
    {
        View::addNamespace('theme', $this->app->make(ThemeRegistry::class)->resolutionPaths());
    }
}
