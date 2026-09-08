<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteHealthController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WooCommerceController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\PerformanceMetricController;
use App\Http\Controllers\PostDisplayController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaxonomyArchiveController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [PostDisplayController::class, 'sitemap'])->name('sitemap');
Route::get('/product-feed.xml', [PostDisplayController::class, 'productFeed'])->name('product-feed');
Route::get('/robots.txt', [PostDisplayController::class, 'robotsTxt'])->name('robots');
Route::get('/llms.txt', [PostDisplayController::class, 'llmsTxt'])->name('llms-txt');
// IndexNow's own verification mechanism: the generated key served back at
// this exact path proves domain ownership, no external registration step.
// Constrained to the real 32-char generated format so this doesn't swallow
// every other `/{anything}.txt` request on the site.
Route::get('/{key}.txt', [PostDisplayController::class, 'indexNowKeyFile'])->where('key', '[a-zA-Z0-9]{32}')->name('indexnow-key');

Route::get('/blog', [PostDisplayController::class, 'index'])->name('blog.index');
Route::get('/dich-vu', [ArchiveController::class, 'services'])->name('services.index');
Route::get('/du-an', [ArchiveController::class, 'projects'])->name('projects.index');
Route::get('/san-pham', [ArchiveController::class, 'products'])->name('products.index');
Route::get('/tim-kiem', SearchController::class)->name('search');
Route::get('/tac-gia/{slug}', [AuthorController::class, 'show'])->name('author.show');
Route::get('/feed.xml', FeedController::class)->name('feed');

// Term archives (/danh-muc/tin-tuc, /the/seo…). Two segments, so this can
// never shadow the single-segment /{slug} catch-all at the bottom of the
// file; the prefix list is constrained so it also can't swallow unrelated
// two-segment URLs a plugin might register later.
Route::get('/{prefix}/{slug}', TaxonomyArchiveController::class)
    ->whereIn('prefix', array_keys(TaxonomyArchiveController::PREFIXES))
    ->name('taxonomy.show');

Route::get('/lien-he', [ContactController::class, 'show'])->name('contact.show');
Route::post('/lien-he', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:10,1');
Route::post('/rum', [PerformanceMetricController::class, 'store'])->name('rum.store')->middleware('throttle:60,1');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', \App\Http\Middleware\TrackAdminActivity::class])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/help', [HelpController::class, 'index'])->name('help');
    Route::get('/analytics', AnalyticsController::class)->name('analytics')->middleware('can:manage_options');
    Route::get('/analytics/recent-activity', [AnalyticsController::class, 'recentActivity'])->name('analytics.recent-activity')->middleware('can:manage_options');
    Route::get('/marketing', MarketingController::class)->name('marketing')->middleware('can:manage_options');
    Route::prefix('health')->name('health.')->middleware('can:manage_options')->group(function () {
        Route::get('/', SiteHealthController::class)->name('index');
        Route::post('/clear-cache', [SiteHealthController::class, 'clearCache'])->name('clear-cache');
        Route::post('/blocked-ips', [SiteHealthController::class, 'storeBlockedIp'])->name('blocked-ips.store');
        Route::delete('/blocked-ips/{blockedIp}', [SiteHealthController::class, 'destroyBlockedIp'])->name('blocked-ips.destroy');
        Route::post('/backup', [SiteHealthController::class, 'runBackup'])->name('backup');
        Route::post('/test-email', [SiteHealthController::class, 'sendTestEmail'])->name('test-email');
    });

    Route::prefix('content')->name('content.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create')->middleware('can:edit_posts');
        Route::post('/', [PostController::class, 'store'])->name('store')->middleware('can:edit_posts');
        Route::delete('/bulk-destroy', [PostController::class, 'bulkDestroy'])->name('bulkDestroy')->middleware('can:delete_posts');
        Route::patch('/bulk-update', [PostController::class, 'bulkUpdate'])->name('bulkUpdate')->middleware('can:edit_posts');
        Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit')->middleware('can:edit_posts');
        Route::put('/{post}', [PostController::class, 'update'])->name('update')->middleware('can:edit_posts');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy')->middleware('can:delete_posts');
    });

    Route::prefix('media')->name('media.')->middleware('can:edit_posts')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/picker', [MediaController::class, 'picker'])->name('picker');
        Route::post('/', [MediaController::class, 'store'])->name('store');
        Route::post('/embed', [MediaController::class, 'storeEmbed'])->name('storeEmbed');
        Route::delete('/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('bulkDestroy');
        Route::patch('/{media}', [MediaController::class, 'update'])->name('update');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('taxonomies')->name('taxonomies.')->middleware('can:manage_options')->group(function () {
        Route::get('/{taxonomy}', [TaxonomyController::class, 'index'])->name('index');
        Route::post('/{taxonomy}', [TaxonomyController::class, 'store'])->name('store');
        Route::put('/{taxonomy}/{term}', [TaxonomyController::class, 'update'])->name('update');
        Route::delete('/{taxonomy}/{term}', [TaxonomyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('users')->name('users.')->middleware('can:manage_users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->middleware('can:manage_options')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('seo')->name('seo.')->middleware('can:manage_options')->group(function () {
        Route::redirect('/', '/admin/seo/titles');

        Route::get('/titles', [SeoController::class, 'titles'])->name('titles');
        Route::put('/titles', [SeoController::class, 'updateTitles'])->name('titles.update');

        Route::get('/sitemap', [SeoController::class, 'sitemap'])->name('sitemap');
        Route::put('/sitemap', [SeoController::class, 'updateSitemap'])->name('sitemap.update');

        Route::get('/product-feed', [SeoController::class, 'productFeed'])->name('product-feed');

        Route::get('/robots', [SeoController::class, 'robots'])->name('robots');
        Route::put('/robots', [SeoController::class, 'updateRobots'])->name('robots.update');

        Route::get('/indexnow', [SeoController::class, 'indexnow'])->name('indexnow');
        Route::put('/indexnow', [SeoController::class, 'updateIndexnow'])->name('indexnow.update');
    });

    Route::prefix('woocommerce')->name('woocommerce.')->group(function () {
        Route::post('/import', [WooCommerceController::class, 'import'])->name('import')->middleware('can:manage_options');
        Route::post('/{post}/resync', [WooCommerceController::class, 'resync'])->name('resync')->middleware('can:edit_posts');
    });

    Route::prefix('redirects')->name('redirects.')->middleware('can:manage_options')->group(function () {
        Route::get('/', [RedirectController::class, 'index'])->name('index');
        Route::post('/', [RedirectController::class, 'store'])->name('store');
        Route::put('/{redirect}', [RedirectController::class, 'update'])->name('update');
        Route::delete('/{redirect}', [RedirectController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->middleware('can:manage_options')->group(function () {
        Route::redirect('/', '/admin/settings/general');

        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');

        Route::get('/contact', [SettingsController::class, 'contact'])->name('contact');
        Route::put('/contact', [SettingsController::class, 'updateContact'])->name('contact.update');

        Route::get('/social', [SettingsController::class, 'social'])->name('social');
        Route::put('/social', [SettingsController::class, 'updateSocial'])->name('social.update');

        Route::get('/integrations', [SettingsController::class, 'integrations'])->name('integrations');
        Route::put('/integrations', [SettingsController::class, 'updateIntegrations'])->name('integrations.update');

        Route::get('/email', [SettingsController::class, 'email'])->name('email');
        Route::put('/email', [SettingsController::class, 'updateEmail'])->name('email.update');
        Route::post('/email/test', [SettingsController::class, 'sendTestEmail'])->name('email.test');

        Route::get('/maintenance', [SettingsController::class, 'maintenance'])->name('maintenance');
        Route::post('/maintenance/toggle', [SettingsController::class, 'toggleMaintenance'])->name('maintenance.toggle');
    });

    Route::prefix('themes')->name('themes.')->middleware('can:manage_options')->group(function () {
        Route::get('/', [ThemeController::class, 'index'])->name('index');
        Route::post('/{theme}/activate', [ThemeController::class, 'activate'])->name('activate');
    });

    Route::prefix('contacts')->name('contacts.')->middleware('can:manage_options')->group(function () {
        Route::get('/', [AdminContactController::class, 'index'])->name('index');
        Route::patch('/{contact}/status', [AdminContactController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{contact}', [AdminContactController::class, 'destroy'])->name('destroy');
    });
});

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'preflight'])->name('preflight');

    Route::get('/database', [InstallController::class, 'showDatabaseForm'])->name('database');
    Route::post('/database/test', [InstallController::class, 'testDatabaseConnection'])->name('database.test');
    Route::post('/database', [InstallController::class, 'storeDatabaseForm'])->name('database.store');

    Route::get('/site', [InstallController::class, 'showSiteForm'])->name('site');
    Route::post('/site', [InstallController::class, 'storeSiteForm'])->name('site.store');

    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});

// Điểm móc để plugin đăng ký route công khai của riêng nó.
//
// Đặt ở ĐÂY chứ không phải chỗ khác là có chủ đích: sau toàn bộ route lõi
// (nên plugin không thể vô tình chiếm mất /login hay /admin), nhưng TRƯỚC
// catch-all /{slug} bên dưới (nếu không thì mọi route của plugin đều bị
// catch-all nuốt trước và trả về 404 "không tìm thấy bài viết").
//
// Plugin dùng như sau:
//     add_action('mxd_routes', function () {
//         Route::get('/duong-dan', fn () => view('...'))->name('...');
//     });
do_action('mxd_routes');

// Catch-all for published posts/pages by slug — must stay last so it never
// shadows /login, /admin/*, /install/* above.
Route::get('/{slug}', [PostDisplayController::class, 'show'])->name('post.show');
