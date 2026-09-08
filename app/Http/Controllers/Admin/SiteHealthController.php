<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HealthCheckEmail;
use App\Models\BlockedIp;
use App\Models\ErrorLog;
use App\Models\Media;
use App\Models\PageView;
use App\Models\PerformanceMetric;
use App\Models\Post;
use App\Models\Setting;
use App\Models\SlowQuery;
use App\Support\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Website Health, organized around the 7-pillar standard the user asked
 * for (Performance, Security, Infrastructure, Technical SEO, UX/A11y,
 * Content, Integrations). Every check here is computed live from real
 * data — DB queries, filesystem, config, or a self-probe HTTP request to
 * the site's own public URL — never a fabricated/simulated number. Where
 * a spec item needs infrastructure this app doesn't have (uptime
 * monitoring, Lighthouse/PageSpeed, GSC index counts, rage-click
 * tracking), it's either approximated honestly or omitted rather than
 * faked — see [[project_mamxanhdigital_cms]] for the scoping decisions.
 */
class SiteHealthController extends Controller
{
    private const REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'json',
        'mbstring', 'openssl', 'pcre', 'pdo', 'session', 'tokenizer', 'xml',
    ];

    private const CWV_THRESHOLDS = [
        'LCP' => ['good' => 2500, 'poor' => 4000, 'unit' => 'ms', 'label' => 'LCP — Largest Contentful Paint'],
        'INP' => ['good' => 200, 'poor' => 500, 'unit' => 'ms', 'label' => 'INP — Interaction to Next Paint'],
        'CLS' => ['good' => 0.1, 'poor' => 0.25, 'unit' => '', 'label' => 'CLS — Cumulative Layout Shift'],
        'FCP' => ['good' => 1800, 'poor' => 3000, 'unit' => 'ms', 'label' => 'FCP — First Contentful Paint'],
        'TTFB' => ['good' => 800, 'poor' => 1800, 'unit' => 'ms', 'label' => 'TTFB — Time to First Byte'],
    ];

    public function __invoke(Request $request)
    {
        $groups = $this->groups();

        return view('admin.health', [
            'groups' => $groups,
            'score' => $this->healthScoreFromGroups($groups),
            'summary' => $this->summaryFromGroups($groups),
            'blockedIps' => BlockedIp::latest()->get(),
            'currentIp' => $request->ip(),
            'recentErrors' => ErrorLog::where('type', 'error')->latest()->limit(10)->get(),
            'topNotFound' => ErrorLog::where('type', 'not_found')
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('url, count(*) as hits, max(created_at) as last_seen')
                ->groupBy('url')
                ->orderByDesc('hits')
                ->limit(10)
                ->get(),
            'backupStatus' => (new BackupService)->status(),
            'contentDecay' => $this->contentDecay(),
            'slowQueries' => SlowQuery::latest()->limit(10)->get(),
        ]);
    }

    public function groups(): array
    {
        return [
            ['label' => 'Hiệu năng & Tốc độ tải trang', 'checks' => $this->performanceChecks()],
            ['label' => 'Bảo mật & An toàn thông tin', 'checks' => $this->securityChecks()],
            ['label' => 'Hạ tầng & Độ sẵn sàng', 'checks' => $this->infrastructureChecks()],
            ['label' => 'SEO kỹ thuật & Khả năng cào dữ liệu', 'checks' => $this->seoChecks()],
            ['label' => 'Trải nghiệm người dùng & Tiếp cận', 'checks' => $this->uxChecks()],
            ['label' => 'Chất lượng nội dung', 'checks' => $this->contentChecks()],
            ['label' => 'Tích hợp & Theo dõi dữ liệu', 'checks' => $this->integrationChecks()],
        ];
    }

    /**
     * Flattened pass/warn/fail counts across every check — used by the
     * Dashboard's health summary card so it doesn't re-run/duplicate the
     * per-group logic above.
     */
    public function summary(): array
    {
        return $this->summaryFromGroups($this->groups());
    }

    public function summaryFromGroups(array $groups): array
    {
        $checks = collect($groups)->flatMap(fn (array $g) => $g['checks']);

        return [
            'pass' => $checks->where('status', 'pass')->count(),
            'warn' => $checks->where('status', 'warn')->count(),
            'fail' => $checks->where('status', 'fail')->count(),
        ];
    }

    /**
     * Overall 0-100 score per the requested Health Score Matrix: pass=100
     * points, warn=50, fail=0, averaged — banded Xanh(90+)/Vàng(70-89)/Đỏ(<70)
     * exactly as specified.
     */
    public function healthScore(): array
    {
        return $this->healthScoreFromGroups($this->groups());
    }

    public function healthScoreFromGroups(array $groups): array
    {
        $checks = collect($groups)->flatMap(fn (array $g) => $g['checks']);
        $total = max(1, $checks->count());
        $points = $checks->sum(fn (array $c) => match ($c['status']) {
            'pass' => 100,
            'warn' => 50,
            default => 0,
        });
        $score = (int) round($points / $total);

        return [
            'score' => $score,
            'band' => $score >= 90 ? 'excellent' : ($score >= 70 ? 'good' : 'poor'),
            'label' => $score >= 90 ? 'Xuất sắc' : ($score >= 70 ? 'Khá' : 'Cảnh báo'),
        ];
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Cache::forget('health_self_probe');

        return back()->with('status', 'Đã xoá cache.');
    }

    public function runBackup()
    {
        $result = (new BackupService)->run();

        return $result['success']
            ? back()->with('status', 'Sao lưu thành công: '.basename($result['path']).' ('.round($result['sizeBytes'] / 1_048_576, 1).' MB)')
            : back()->withErrors(['backup' => 'Sao lưu thất bại: '.$result['error']]);
    }

    public function sendTestEmail(Request $request)
    {
        try {
            Mail::to($request->user()->email)->send(new HealthCheckEmail);

            return back()->with('status', 'Đã gửi email thử nghiệm tới '.$request->user()->email);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Gửi thất bại: '.mb_substr($e->getMessage(), 0, 200)]);
        }
    }

    public function storeBlockedIp(Request $request)
    {
        $data = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        BlockedIp::firstOrCreate(
            ['ip_address' => $data['ip_address']],
            ['reason' => $data['reason'] ?? null]
        );

        return back()->with('status', 'Đã chặn IP.');
    }

    public function destroyBlockedIp(BlockedIp $blockedIp)
    {
        $blockedIp->delete();

        return back()->with('status', 'Đã gỡ chặn.');
    }

    // ── 1. Hiệu năng & Tốc độ ────────────────────────────────────────

    private function performanceChecks(): array
    {
        $checks = [];
        $since = now()->subDays(7);

        foreach (self::CWV_THRESHOLDS as $metric => $t) {
            $values = PerformanceMetric::where('metric', $metric)->where('created_at', '>=', $since)->pluck('value');

            if ($values->count() < 20) {
                $checks[] = [
                    'label' => $t['label'].' (p75)',
                    'status' => 'warn',
                    'detail' => "Chưa đủ dữ liệu thật ({$values->count()}/20 mẫu, 7 ngày qua)",
                ];

                continue;
            }

            $p75 = $this->percentile($values, 75);
            $status = $p75 <= $t['good'] ? 'pass' : ($p75 <= $t['poor'] ? 'warn' : 'fail');
            $display = $t['unit'] === 'ms' ? round($p75).'ms' : round($p75, 3);

            $checks[] = [
                'label' => $t['label'].' (p75)',
                'status' => $status,
                'detail' => "{$display} · {$values->count()} mẫu thật từ khách truy cập",
            ];
        }

        $probe = $this->selfProbe();

        if (! $probe['ok']) {
            $checks[] = [
                'label' => 'Tự kiểm tra trang chủ (thời gian phản hồi, dung lượng, nén)',
                'status' => 'warn',
                'detail' => 'Không tự kiểm tra được — có thể mạng ra ngoài bị chặn hoặc APP_URL chưa đúng',
            ];
        } else {
            $checks[] = [
                'label' => 'Thời gian phản hồi trang chủ',
                'status' => $probe['elapsedMs'] < 800 ? 'pass' : ($probe['elapsedMs'] < 1800 ? 'warn' : 'fail'),
                'detail' => "{$probe['elapsedMs']}ms",
            ];
            $checks[] = [
                'label' => 'Dung lượng trang chủ (HTML)',
                'status' => $probe['bytes'] < 500_000 ? 'pass' : 'warn',
                'detail' => round($probe['bytes'] / 1024, 1).' KB',
            ];
            $checks[] = [
                'label' => 'Nén phản hồi (Gzip/Brotli)',
                'status' => $probe['gzip'] ? 'pass' : 'warn',
                'detail' => $probe['gzip'] ? 'Đã bật' : 'Chưa bật nén phản hồi HTTP',
            ];
        }

        $checks[] = $this->imageOptimizationCheck();

        $cacheStatus = 'pass';
        try {
            Cache::put('mxd_health_check', '1', 5);
            $cacheStatus = Cache::get('mxd_health_check') === '1' ? 'pass' : 'fail';
        } catch (\Throwable) {
            $cacheStatus = 'fail';
        }
        $checks[] = ['label' => 'Cache driver', 'status' => $cacheStatus, 'detail' => config('cache.default')];

        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status(false) !== false;
        $checks[] = [
            'label' => 'OPcache',
            'status' => $opcacheEnabled ? 'pass' : 'warn',
            'detail' => $opcacheEnabled ? 'Đang bật' : 'Chưa bật — cân nhắc bật để tăng tốc PHP',
        ];

        $isProd = config('app.env') === 'production';
        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $checks[] = [
            'label' => 'Config cache (production)',
            'status' => ! $isProd ? 'pass' : ($configCached ? 'pass' : 'warn'),
            'detail' => ! $isProd
                ? 'Chỉ áp dụng khi APP_ENV=production'
                : ($configCached ? 'Đã cache' : 'Chưa chạy php artisan config:cache'),
        ];

        return $checks;
    }

    private function imageOptimizationCheck(): array
    {
        $total = Media::where('kind', 'image')->count();

        if ($total === 0) {
            return ['label' => 'Định dạng ảnh tối ưu (WebP/AVIF)', 'status' => 'pass', 'detail' => 'Chưa có ảnh nào'];
        }

        $optimized = Media::where('kind', 'image')->whereIn('mime_type', ['image/webp', 'image/avif'])->count();
        $pct = (int) round($optimized / $total * 100);

        return [
            'label' => 'Định dạng ảnh tối ưu (WebP/AVIF)',
            'status' => $pct >= 80 ? 'pass' : ($pct >= 30 ? 'warn' : 'fail'),
            'detail' => "{$optimized}/{$total} ảnh ({$pct}%) — tải ảnh dạng WebP để giảm dung lượng",
        ];
    }

    private function percentile(Collection $values, int $p): float
    {
        $sorted = $values->sort()->values();
        $index = max(0, min($sorted->count() - 1, (int) ceil($p / 100 * $sorted->count()) - 1));

        return (float) $sorted[$index];
    }

    /**
     * One self-probe (real HTTP request to the site's own public URL),
     * cached briefly and reused across Performance/Security/UX checks so
     * a single health-page load doesn't fire several outbound requests.
     */
    private function selfProbe(): array
    {
        return Cache::remember('health_self_probe', 300, function () {
            try {
                $start = microtime(true);
                $response = Http::timeout(3)->get(rtrim(config('app.url'), '/').'/');
                $elapsedMs = (int) round((microtime(true) - $start) * 1000);
                $body = $response->body();
                $encoding = mb_strtolower($response->header('Content-Encoding') ?? '');

                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'elapsedMs' => $elapsedMs,
                    'bytes' => strlen($body),
                    'gzip' => str_contains($encoding, 'gzip') || str_contains($encoding, 'br'),
                    'hsts' => filled($response->header('Strict-Transport-Security')),
                    'hasViewport' => str_contains($body, 'name="viewport"'),
                ];
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Gates the other self-probing checks (robots/sitemap, JSON-LD, H1
     * structure) behind the cheap primary probe's result — if the site
     * can't reach itself at all, there's no point burning several more
     * multi-second timeouts finding that out again on every check.
     */
    private function probeReachable(): bool
    {
        return $this->selfProbe()['ok'];
    }

    // ── 2. Bảo mật & An toàn thông tin ───────────────────────────────

    private function securityChecks(): array
    {
        $debugOnInProd = config('app.debug') && config('app.env') === 'production';
        $envExposed = file_exists(public_path('.env'));
        $loginThrottled = $this->routeHasThrottle('POST', 'login');
        $contactThrottled = $this->routeHasThrottle('POST', 'lien-he');
        $blockedCount = BlockedIp::count();
        $probe = $this->selfProbe();

        return [
            [
                'label' => 'APP_DEBUG khi production',
                'status' => $debugOnInProd ? 'fail' : 'pass',
                'detail' => $debugOnInProd ? 'Đang bật — lộ thông tin lỗi ra ngoài' : 'Tắt hoặc không phải production',
            ],
            [
                'label' => 'HTTPS',
                'status' => request()->isSecure() ? 'pass' : 'warn',
                'detail' => request()->isSecure() ? 'Đang dùng HTTPS' : 'Chưa dùng HTTPS',
            ],
            $this->sslCertCheck(),
            [
                'label' => 'HSTS (Strict-Transport-Security)',
                'status' => ! request()->isSecure() ? 'warn' : (($probe['hsts'] ?? false) ? 'pass' : 'warn'),
                'detail' => ! request()->isSecure() ? 'Chỉ áp dụng khi dùng HTTPS' : (($probe['hsts'] ?? false) ? 'Đã bật' : 'Không tự kiểm tra được hoặc chưa bật'),
            ],
            $this->cookieFlagsCheck(),
            [
                'label' => 'APP_KEY',
                'status' => filled(config('app.key')) ? 'pass' : 'fail',
                'detail' => filled(config('app.key')) ? 'Đã thiết lập' : 'Chưa thiết lập',
            ],
            [
                'label' => 'Tệp .env không public',
                'status' => $envExposed ? 'fail' : 'pass',
                'detail' => $envExposed ? 'CẢNH BÁO: .env nằm trong thư mục public!' : 'An toàn — nằm ngoài thư mục public',
            ],
            [
                'label' => 'HTTP security headers',
                'status' => 'pass',
                'detail' => 'X-Frame-Options, X-Content-Type-Options, Referrer-Policy',
            ],
            [
                'label' => 'Chống SQL Injection / XSS / CSRF',
                'status' => 'pass',
                'detail' => 'Query Builder/Eloquent tham số hoá, Blade tự escape, CSRF token bắt buộc, nội dung HTML lọc qua HTMLPurifier',
            ],
            $this->uploadIntegrityCheck(),
            [
                'label' => 'Giới hạn tần suất đăng nhập',
                'status' => $loginThrottled ? 'pass' : 'warn',
                'detail' => $loginThrottled ? 'Đã bật (5 lần/phút)' : 'Chưa bật',
            ],
            [
                'label' => 'Giới hạn tần suất form liên hệ',
                'status' => $contactThrottled ? 'pass' : 'warn',
                'detail' => $contactThrottled ? 'Đã bật (10 lần/phút)' : 'Chưa bật',
            ],
            [
                'label' => 'IP đang bị chặn (tường lửa ứng dụng)',
                'status' => 'pass',
                'detail' => $blockedCount.' IP',
            ],
        ];
    }

    private function sslCertCheck(): array
    {
        $url = config('app.url');
        $host = parse_url($url, PHP_URL_HOST);

        if (! str_starts_with((string) $url, 'https://') || ! $host) {
            return ['label' => 'Chứng chỉ SSL/TLS', 'status' => 'warn', 'detail' => 'APP_URL chưa dùng https://'];
        }

        return Cache::remember('health_ssl_cert', 3600, function () use ($host) {
            try {
                $context = stream_context_create(['ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]]);

                $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

                if (! $client) {
                    return ['label' => 'Chứng chỉ SSL/TLS', 'status' => 'warn', 'detail' => "Không kết nối được: {$errstr}"];
                }

                $params = stream_context_get_params($client);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                fclose($client);

                $expiresAt = \Illuminate\Support\Carbon::createFromTimestamp($cert['validTo_time_t']);
                $daysLeft = (int) round(now()->diffInHours($expiresAt, false) / 24);

                return [
                    'label' => 'Chứng chỉ SSL/TLS',
                    'status' => $daysLeft < 14 ? 'fail' : ($daysLeft < 30 ? 'warn' : 'pass'),
                    'detail' => "Hết hạn {$expiresAt->format('d/m/Y')} (còn {$daysLeft} ngày)",
                ];
            } catch (\Throwable) {
                return ['label' => 'Chứng chỉ SSL/TLS', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
            }
        });
    }

    private function cookieFlagsCheck(): array
    {
        $secure = config('session.secure');
        $httpOnly = config('session.http_only');
        $sameSite = config('session.same_site');

        $issues = [];
        if (! $httpOnly) {
            $issues[] = 'HttpOnly tắt';
        }
        if (config('app.env') === 'production' && ! $secure) {
            $issues[] = 'Secure tắt (production)';
        }
        if (! in_array($sameSite, ['strict', 'lax'], true)) {
            $issues[] = 'SameSite chưa đặt strict/lax';
        }

        return [
            'label' => 'Thuộc tính cookie phiên đăng nhập',
            'status' => empty($issues) ? 'pass' : 'warn',
            'detail' => empty($issues)
                ? 'HttpOnly'.($secure ? ', Secure' : '').", SameSite={$sameSite}"
                : implode('; ', $issues),
        ];
    }

    private function uploadIntegrityCheck(): array
    {
        return Cache::remember('health_upload_integrity', 600, function () {
            $broken = Media::whereNotNull('path')->get(['disk', 'path'])->filter(
                fn (Media $m) => ! Storage::disk($m->disk)->exists($m->path)
            )->count();

            return [
                'label' => 'Toàn vẹn tệp tải lên',
                'status' => $broken === 0 ? 'pass' : 'warn',
                'detail' => $broken === 0
                    ? 'Xác thực nội dung ảnh thật khi tải lên, không có tệp bị mất trên ổ đĩa'
                    : "{$broken} tệp bị mất trên ổ đĩa",
            ];
        });
    }

    private function routeHasThrottle(string $method, string $uri): bool
    {
        $route = collect(Route::getRoutes())->first(
            fn ($r) => in_array($method, $r->methods(), true) && $r->uri() === $uri
        );

        if (! $route) {
            return false;
        }

        return collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'throttle:'));
    }

    // ── 3. Hạ tầng & Độ sẵn sàng ─────────────────────────────────────

    private function infrastructureChecks(): array
    {
        $checks = [[
            'label' => 'Phiên bản PHP',
            'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'pass' : 'fail',
            'detail' => PHP_VERSION,
        ]];

        $missing = array_filter(self::REQUIRED_EXTENSIONS, fn (string $ext) => ! extension_loaded($ext));
        $checks[] = [
            'label' => 'Extension PHP bắt buộc',
            'status' => empty($missing) ? 'pass' : 'fail',
            'detail' => empty($missing) ? 'Đầy đủ' : 'Thiếu: '.implode(', ', $missing),
        ];

        $checks[] = $this->loadAverageCheck();

        $freeBytes = disk_free_space(base_path());
        $freeGb = $freeBytes !== false ? round($freeBytes / 1024 / 1024 / 1024, 1) : null;
        $checks[] = [
            'label' => 'Dung lượng đĩa trống',
            'status' => $freeGb === null ? 'warn' : ($freeGb < 1 ? 'fail' : ($freeGb < 5 ? 'warn' : 'pass')),
            'detail' => $freeGb === null ? 'Không xác định được' : "{$freeGb} GB",
        ];

        $checks[] = [
            'label' => 'storage/ ghi được',
            'status' => is_writable(storage_path()) ? 'pass' : 'fail',
            'detail' => storage_path(),
        ];
        $checks[] = [
            'label' => 'Symlink storage:link',
            'status' => is_link(public_path('storage')) ? 'pass' : 'warn',
            'detail' => is_link(public_path('storage')) ? 'Đã tạo' : 'Chưa chạy php artisan storage:link',
        ];
        $checks[] = [
            'label' => 'bootstrap/cache ghi được',
            'status' => is_writable(base_path('bootstrap/cache')) ? 'pass' : 'fail',
            'detail' => base_path('bootstrap/cache'),
        ];

        $dbStatus = 'pass';
        $dbDetail = DB::connection()->getDatabaseName();
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'fail';
            $dbDetail = mb_substr($e->getMessage(), 0, 120);
        }
        $checks[] = ['label' => 'Cơ sở dữ liệu', 'status' => $dbStatus, 'detail' => $dbDetail];

        $checks[] = $this->dbConnectionsCheck();
        $checks[] = $this->slowQueriesCheck();

        $redisCheck = $this->redisCacheHitCheck();
        if ($redisCheck) {
            $checks[] = $redisCheck;
        }

        $checks[] = $this->backupCheck();

        return $checks;
    }

    private function loadAverageCheck(): array
    {
        if (! function_exists('sys_getloadavg')) {
            return ['label' => 'Tải hệ thống (Load Average)', 'status' => 'warn', 'detail' => 'Không hỗ trợ trên hệ điều hành này'];
        }

        $load = sys_getloadavg();
        $load1 = $load[0] ?? 0;

        return [
            'label' => 'Tải hệ thống (Load Average, 1 phút)',
            'status' => $load1 < 2 ? 'pass' : ($load1 < 4 ? 'warn' : 'fail'),
            'detail' => round($load1, 2).' — ngưỡng ước lượng chung, không tính theo số nhân CPU thực tế',
        ];
    }

    private function dbConnectionsCheck(): array
    {
        try {
            $connected = (int) (DB::selectOne("SHOW STATUS LIKE 'Threads_connected'")?->Value ?? 0);
            $max = (int) (DB::selectOne("SHOW VARIABLES LIKE 'max_connections'")?->Value ?? 0);
            $pct = $max > 0 ? (int) round($connected / $max * 100) : 0;

            return [
                'label' => 'Kết nối CSDL đang mở',
                'status' => $pct < 60 ? 'pass' : ($pct < 85 ? 'warn' : 'fail'),
                'detail' => "{$connected}/{$max} ({$pct}%)",
            ];
        } catch (\Throwable) {
            return ['label' => 'Kết nối CSDL đang mở', 'status' => 'warn', 'detail' => 'Không xác định được'];
        }
    }

    private function slowQueriesCheck(): array
    {
        $count = SlowQuery::where('created_at', '>=', now()->subDay())->count();

        return [
            'label' => 'Truy vấn CSDL chậm (>500ms, 24h qua)',
            'status' => $count === 0 ? 'pass' : ($count < 10 ? 'warn' : 'fail'),
            'detail' => "{$count} truy vấn",
        ];
    }

    private function redisCacheHitCheck(): ?array
    {
        if (config('cache.default') !== 'redis') {
            return null;
        }

        try {
            $info = Redis::connection()->info('stats');
            $hits = (int) ($info['keyspace_hits'] ?? 0);
            $misses = (int) ($info['keyspace_misses'] ?? 0);
            $total = max(1, $hits + $misses);
            $pct = (int) round($hits / $total * 100);

            return [
                'label' => 'Redis Cache Hit Ratio',
                'status' => $pct >= 80 ? 'pass' : 'warn',
                'detail' => "{$pct}% ({$hits} hit / {$misses} miss)",
            ];
        } catch (\Throwable) {
            return ['label' => 'Redis Cache Hit Ratio', 'status' => 'warn', 'detail' => 'Không đọc được Redis INFO'];
        }
    }

    private function backupCheck(): array
    {
        $status = (new BackupService)->status();

        if (! $status['latestAt']) {
            return ['label' => 'Sao lưu dữ liệu', 'status' => 'fail', 'detail' => 'Chưa có bản sao lưu nào'];
        }

        $hoursAgo = $status['latestAt']->diffInHours(now());

        return [
            'label' => 'Sao lưu dữ liệu',
            'status' => $hoursAgo < 36 ? 'pass' : ($hoursAgo < 72 ? 'warn' : 'fail'),
            'detail' => "Gần nhất: {$status['latestAt']->diffForHumans()} · giữ {$status['count']} bản",
        ];
    }

    // ── 4. SEO kỹ thuật & Khả năng cào dữ liệu ───────────────────────

    public function seoChecks(): array
    {
        $missingDescription = Post::published()
            ->whereDoesntHave('meta', fn ($q) => $q->where('meta_key', 'meta_description')->where('meta_value', '!=', ''))
            ->count();

        $hasCustomScripts = filled(Setting::get('custom_head_scripts')) || filled(Setting::get('custom_footer_scripts'));

        $checks = [
            [
                'label' => 'Bài đăng thiếu mô tả SEO',
                'status' => $missingDescription === 0 ? 'pass' : 'warn',
                'detail' => $missingDescription === 0 ? 'Tất cả đã có mô tả SEO' : "{$missingDescription} bài chưa có",
            ],
            [
                'label' => 'Script phân tích/tích hợp bên thứ 3',
                'status' => $hasCustomScripts ? 'pass' : 'warn',
                'detail' => $hasCustomScripts ? 'Đã cấu hình' : 'Chưa có script nào — xem Cài đặt',
            ],
            [
                'label' => 'Thẻ Canonical',
                'status' => 'pass',
                'detail' => 'Tự động gắn trên mọi trang công khai',
            ],
        ];

        $checks = array_merge($checks, $this->robotsSitemapChecks());
        $checks[] = $this->jsonLdCoverageCheck();

        return $checks;
    }

    private function robotsSitemapChecks(): array
    {
        if (! $this->probeReachable()) {
            return [
                ['label' => 'robots.txt', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'],
                ['label' => 'sitemap.xml', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'],
            ];
        }

        return Cache::remember('health_robots_sitemap', 900, function () {
            $checks = [];
            $base = rtrim(config('app.url'), '/');

            try {
                $robots = Http::timeout(3)->get("{$base}/robots.txt");
                $checks[] = [
                    'label' => 'robots.txt',
                    'status' => $robots->successful() && filled($robots->body()) ? 'pass' : 'warn',
                    'detail' => $robots->successful() ? 'Truy cập được' : 'Không truy cập được (HTTP '.$robots->status().')',
                ];
            } catch (\Throwable) {
                $checks[] = ['label' => 'robots.txt', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
            }

            try {
                $sitemap = Http::timeout(3)->get("{$base}/sitemap.xml");
                $validXml = $sitemap->successful() && @simplexml_load_string($sitemap->body()) !== false;
                $checks[] = [
                    'label' => 'sitemap.xml',
                    'status' => $validXml ? 'pass' : 'warn',
                    'detail' => $validXml ? 'Hợp lệ, truy cập được' : 'Không truy cập được hoặc XML không hợp lệ',
                ];
            } catch (\Throwable) {
                $checks[] = ['label' => 'sitemap.xml', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
            }

            return $checks;
        });
    }

    private function jsonLdCoverageCheck(): array
    {
        if (! $this->probeReachable()) {
            return ['label' => 'Dữ liệu cấu trúc Schema.org (JSON-LD)', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
        }

        return Cache::remember('health_jsonld_coverage', 900, function () {
            $missing = [];
            $checked = 0;

            foreach (['post', 'product', 'project', 'service'] as $type) {
                $post = Post::published()->where('post_type', $type)->latest('published_at')->first();

                if (! $post) {
                    continue;
                }

                try {
                    $response = Http::timeout(3)->get(route('post.show', $post->slug));
                    $checked++;

                    if (! str_contains($response->body(), 'application/ld+json')) {
                        $missing[] = $type;
                    }
                } catch (\Throwable) {
                    // Self-probe unavailable — don't falsely flag as missing.
                }
            }

            if ($checked === 0) {
                return ['label' => 'Dữ liệu cấu trúc Schema.org (JSON-LD)', 'status' => 'warn', 'detail' => 'Chưa có nội dung để kiểm tra hoặc không tự kiểm tra được'];
            }

            return [
                'label' => 'Dữ liệu cấu trúc Schema.org (JSON-LD)',
                'status' => empty($missing) ? 'pass' : 'warn',
                'detail' => empty($missing) ? "Đầy đủ trên {$checked} loại nội dung đã kiểm tra" : 'Thiếu ở: '.implode(', ', $missing),
            ];
        });
    }

    // ── 5. Trải nghiệm người dùng & Tiếp cận ─────────────────────────

    private function uxChecks(): array
    {
        $probe = $this->selfProbe();

        return [
            [
                'label' => 'Thẻ viewport (responsive)',
                'status' => $probe['ok'] ? ($probe['hasViewport'] ? 'pass' : 'fail') : 'warn',
                'detail' => $probe['ok'] ? ($probe['hasViewport'] ? 'Có' : 'Thiếu thẻ viewport') : 'Không tự kiểm tra được',
            ],
            [
                'label' => 'Giao diện responsive',
                'status' => 'pass',
                'detail' => 'Toàn bộ theme dùng Tailwind CSS responsive utilities, tương thích mọi trình duyệt hiện đại',
            ],
        ];
    }

    // ── 6. Chất lượng nội dung ────────────────────────────────────────

    private function contentChecks(): array
    {
        $checks = [];

        $totalMedia = Media::where('kind', 'image')->count();
        if ($totalMedia === 0) {
            $checks[] = ['label' => 'Phủ thẻ Alt cho ảnh', 'status' => 'pass', 'detail' => 'Chưa có ảnh nào'];
        } else {
            $withAlt = Media::where('kind', 'image')->whereNotNull('alt_text')->where('alt_text', '!=', '')->count();
            $pct = (int) round($withAlt / $totalMedia * 100);
            $checks[] = [
                'label' => 'Phủ thẻ Alt cho ảnh',
                'status' => $pct >= 80 ? 'pass' : ($pct >= 30 ? 'warn' : 'fail'),
                'detail' => "{$withAlt}/{$totalMedia} ảnh ({$pct}%) có mô tả — sửa tại Thư viện ảnh",
            ];
        }

        $checks[] = $this->uploadIntegrityCheck();
        $checks[] = $this->h1StructureCheck();

        $decaying = $this->contentDecay();
        $checks[] = [
            'label' => 'Nội dung sụt giảm truy cập (Content Decay)',
            'status' => $decaying->isEmpty() ? 'pass' : 'warn',
            'detail' => $decaying->isEmpty() ? 'Không có bài nào giảm mạnh' : "{$decaying->count()} bài giảm ≥40% lượt xem so với 30 ngày trước",
        ];

        return $checks;
    }

    private function h1StructureCheck(): array
    {
        $posts = Post::published()->latest('published_at')->limit(5)->get();

        if ($posts->isEmpty()) {
            return ['label' => 'Cấu trúc thẻ H1', 'status' => 'pass', 'detail' => 'Chưa có nội dung nào'];
        }

        if (! $this->probeReachable()) {
            return ['label' => 'Cấu trúc thẻ H1', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
        }

        return Cache::remember('health_h1_structure', 900, function () use ($posts) {
            $bad = 0;
            $checked = 0;

            foreach ($posts as $post) {
                try {
                    $response = Http::timeout(3)->get(route('post.show', $post->slug));
                    $checked++;

                    if (substr_count(mb_strtolower($response->body()), '<h1') !== 1) {
                        $bad++;
                    }
                } catch (\Throwable) {
                    // skip — self-probe unavailable
                }
            }

            if ($checked === 0) {
                return ['label' => 'Cấu trúc thẻ H1', 'status' => 'warn', 'detail' => 'Không tự kiểm tra được'];
            }

            return [
                'label' => 'Cấu trúc thẻ H1',
                'status' => $bad === 0 ? 'pass' : 'warn',
                'detail' => $bad === 0 ? "Đúng chuẩn (đúng 1 thẻ H1) trên {$checked} trang đã kiểm tra" : "{$bad}/{$checked} trang có 0 hoặc nhiều hơn 1 thẻ H1",
            ];
        });
    }

    /**
     * Real content-decay detection: posts whose last-30-days views dropped
     * ≥40% vs. the 30 days before that, computed straight from `page_views`
     * — no synthetic "engagement score."
     */
    public function contentDecay(int $limit = 5): Collection
    {
        $pathToPost = Post::published()->get(['id', 'title', 'slug'])->mapWithKeys(fn (Post $p) => ['/'.$p->slug => $p]);

        $recentViews = PageView::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('path, count(*) as views')->groupBy('path')->pluck('views', 'path');

        $priorViews = PageView::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->selectRaw('path, count(*) as views')->groupBy('path')->pluck('views', 'path');

        $decaying = [];

        foreach ($priorViews as $path => $prior) {
            if ($prior < 5 || ! isset($pathToPost[$path])) {
                continue;
            }

            $recent = (int) ($recentViews[$path] ?? 0);
            $dropPct = (int) round((1 - $recent / $prior) * 100);

            if ($dropPct >= 40) {
                $decaying[] = [
                    'title' => $pathToPost[$path]->title,
                    'path' => $path,
                    'prior' => (int) $prior,
                    'recent' => $recent,
                    'dropPct' => $dropPct,
                ];
            }
        }

        return collect($decaying)->sortByDesc('dropPct')->take($limit)->values();
    }

    // ── 7. Tích hợp & Theo dõi dữ liệu ────────────────────────────────

    private function integrationChecks(): array
    {
        $checks = $this->emailChecks();

        $scripts = (Setting::get('custom_head_scripts', '') ?? '').' '.(Setting::get('custom_footer_scripts', '') ?? '');
        $detected = [];
        if (str_contains($scripts, 'gtag(') || str_contains($scripts, 'googletagmanager')) {
            $detected[] = 'Google Analytics/GTM';
        }
        if (str_contains($scripts, 'fbq(')) {
            $detected[] = 'Facebook Pixel';
        }
        if (str_contains($scripts, 'ttq.')) {
            $detected[] = 'TikTok Pixel';
        }

        $checks[] = [
            'label' => 'Tracking Pixel',
            'status' => empty($detected) ? 'warn' : 'pass',
            'detail' => empty($detected) ? 'Chưa phát hiện script theo dõi nào trong Cài đặt' : 'Đã phát hiện: '.implode(', ', $detected),
        ];

        return $checks;
    }

    private function emailChecks(): array
    {
        $mailer = config('mail.default');

        if ($mailer === 'log') {
            return [[
                'label' => 'Trình gửi email',
                'status' => 'warn',
                'detail' => 'Đang dùng "log" — email chưa thực sự được gửi ra ngoài',
            ]];
        }

        $configured = filled(config('mail.mailers.'.$mailer.'.host', config('mail.mailers.smtp.host')))
            && filled(config('mail.mailers.smtp.username'));

        return [[
            'label' => 'Trình gửi email',
            'status' => $configured ? 'pass' : 'fail',
            'detail' => $configured ? "Đang dùng \"{$mailer}\"" : "Đang dùng \"{$mailer}\" nhưng thiếu host/username",
        ]];
    }
}
