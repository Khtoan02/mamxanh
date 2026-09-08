<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EnvironmentWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;

/**
 * Four-step web installer (system check -> database -> site & admin ->
 * lock & completion), matching SRS section III "SETUP WIZARD".
 */
class InstallController extends Controller
{
    /** Step 1: System Pre-flight Check */
    public function preflight(Request $request)
    {
        $checks = $this->runPreflightChecks();
        $allPassed = collect($checks)->every(fn (array $c) => $c['passed']);

        return view('install.preflight', compact('checks', 'allPassed'));
    }

    /** Step 2: Database Configuration - show form */
    public function showDatabaseForm(Request $request)
    {
        return view('install.database', [
            'defaults' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', ''),
                'username' => env('DB_USERNAME', 'root'),
                'prefix' => env('DB_TABLE_PREFIX', 'mxd_'),
            ],
        ]);
    }

    /** Step 2: "Test Connection" button (AJAX) */
    public function testDatabaseConnection(Request $request)
    {
        $data = $this->validateDatabaseInput($request);

        $result = $this->attemptConnection($data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /** Step 2: submit - validate, test, persist to .env, run migrations */
    public function storeDatabaseForm(Request $request, EnvironmentWriter $env)
    {
        $data = $this->validateDatabaseInput($request);

        $result = $this->attemptConnection($data);

        if (! $result['success']) {
            return back()->withInput()->withErrors(['connection' => $result['message']]);
        }

        $env->set([
            'DB_HOST' => $data['host'],
            'DB_PORT' => $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'] ?? '',
            'DB_TABLE_PREFIX' => $data['prefix'],
        ]);

        $this->applyRuntimeDatabaseConfig($data);

        Artisan::call('migrate', ['--force' => true]);

        // Ảnh upload nằm ở content/uploads và được phục vụ qua symlink
        // public/storage. Không có symlink thì mọi ảnh trong Thư viện ảnh
        // đều 404 — trước đây bước này chỉ được PHÁT HIỆN ở trang Sức khoẻ
        // Website chứ không ai tạo giúp, người cài bằng FTP không có cách
        // nào chạy `php artisan storage:link`.
        // Bọc try/catch vì một số shared hosting tắt hàm symlink(); khi đó
        // trang Sức khoẻ Website vẫn báo đúng là chưa tạo được.
        try {
            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $request->session()->put('install.database_ready', true);

        return redirect('/install/site');
    }

    /** Step 3: Site & Admin Initialization - show form */
    public function showSiteForm(Request $request)
    {
        if (! $request->session()->get('install.database_ready')) {
            return redirect('/install/database');
        }

        return view('install.site');
    }

    /** Step 3: submit - persist site info, create Super Admin */
    public function storeSiteForm(Request $request, EnvironmentWriter $env)
    {
        if (! $request->session()->get('install.database_ready')) {
            return redirect('/install/database');
        }

        $data = $request->validate([
            'site_title' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', 'string', 'in:en,vi'],
            'timezone' => ['required', 'string', 'timezone'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $env->set([
            'APP_NAME' => $data['site_title'],
            'SITE_TAGLINE' => $data['site_tagline'] ?? '',
            'APP_LOCALE' => $data['locale'],
            'APP_TIMEZONE' => $data['timezone'],

            // BẮT BUỘC khoá lại khi cài xong. Trang lỗi ở chế độ debug của
            // Laravel in ra toàn bộ biến môi trường, gồm cả mật khẩu CSDL —
            // để nguyên APP_DEBUG=true nghĩa là mỗi website cài từ bộ mã
            // nguồn này đều rò rỉ thông tin ngay khi gặp lỗi đầu tiên.
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',

            // Không có APP_URL đúng thì mọi link tuyệt đối, ảnh, sitemap và
            // canonical đều trỏ về http://localhost.
            'APP_URL' => rtrim(url('/'), '/'),
        ]);

        User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => 'super_admin',
        ]);

        file_put_contents(storage_path('app/install.lock'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_version' => config('mxd.version'),
        ], JSON_PRETTY_PRINT));

        $request->session()->forget('install.database_ready');

        return redirect('/install/finish');
    }

    /** Step 4: Lock & Completion */
    public function finish(Request $request)
    {
        return view('install.finish');
    }

    /**
     * @return array{host: string, port: string, database: string, username: string, password: ?string, prefix: string}
     */
    private function validateDatabaseInput(Request $request): array
    {
        return $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'numeric'],
            'database' => ['required', 'string', 'max:64'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_]*$/'],
        ]);
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: ?string}  $data
     * @return array{success: bool, message: string}
     */
    private function attemptConnection(array $data): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $data['host'], $data['port'], $data['database']);
            new PDO($dsn, $data['username'], $data['password'] ?? '', [
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return ['success' => true, 'message' => 'Kết nối thành công.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $this->humanizePdoError($e)];
        }
    }

    private function humanizePdoError(PDOException $e): string
    {
        $message = $e->getMessage();

        return match (true) {
            str_contains($message, 'Unknown database') => 'Database chưa tồn tại. Hãy tạo database này trước, hoặc kiểm tra lại tên.',
            str_contains($message, 'Access denied') => 'Sai username hoặc password.',
            str_contains($message, 'Connection refused'), str_contains($message, "Name or service not known") => 'Không kết nối được tới host/port đã nhập — kiểm tra MySQL đã chạy chưa.',
            default => 'Lỗi kết nối: '.$message,
        };
    }

    /**
     * @param  array{host: string, port: string, database: string, username: string, password: ?string}  $data
     */
    private function applyRuntimeDatabaseConfig(array $data): void
    {
        config(['database.connections.mysql.host' => $data['host']]);
        config(['database.connections.mysql.port' => $data['port']]);
        config(['database.connections.mysql.database' => $data['database']]);
        config(['database.connections.mysql.username' => $data['username']]);
        config(['database.connections.mysql.password' => $data['password'] ?? '']);
        config(['database.connections.mysql.prefix' => $data['prefix'] ?? '']);

        DB::purge('mysql');
    }

    /**
     * @return array<int, array{label: string, passed: bool, detail: string}>
     */
    private function runPreflightChecks(): array
    {
        $requiredExtensions = ['pdo_mysql', 'mbstring', 'json', 'curl', 'openssl', 'zip', 'xml'];
        $checks = [];

        // Đọc từ config/mxd.php — một nguồn sự thật duy nhất cho yêu cầu hệ
        // thống, thay vì lặp lại con số ở README, preflight và composer.json
        // rồi để chúng lệch nhau qua thời gian.
        $minPhp = config('mxd.requires.php', '8.2.0');
        $phpOk = version_compare(PHP_VERSION, $minPhp, '>=');
        $checks[] = [
            'label' => 'PHP >= '.$minPhp,
            'passed' => $phpOk,
            'detail' => 'Hiện tại: PHP '.PHP_VERSION,
        ];

        foreach ($requiredExtensions as $ext) {
            $checks[] = [
                'label' => "Extension: {$ext}",
                'passed' => extension_loaded($ext),
                'detail' => extension_loaded($ext) ? 'Đã bật' : 'Chưa cài / chưa bật',
            ];
        }

        $checks[] = [
            'label' => 'Extension: gd hoặc imagick',
            'passed' => extension_loaded('gd') || extension_loaded('imagick'),
            'detail' => extension_loaded('gd') ? 'gd đã bật' : (extension_loaded('imagick') ? 'imagick đã bật' : 'Chưa cài gd/imagick'),
        ];

        // mod_rewrite: không có nó thì mọi route của Laravel đều 404. Trước
        // đây người dùng chỉ phát hiện ra sau khi cài xong, khi bấm vào bất
        // kỳ link nào cũng lỗi — mà lúc đó rất khó đoán nguyên nhân.
        // Chỉ kiểm tra được trên Apache/PHP module; môi trường khác (Nginx,
        // PHP-FPM, CLI) trả về null nên báo là "không xác định" thay vì báo
        // hỏng sai.
        $rewrite = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules(), true) : null;
        $checks[] = [
            'label' => 'Apache mod_rewrite',
            'passed' => $rewrite !== false,
            'detail' => match ($rewrite) {
                true => 'Đã bật',
                false => 'CHƯA bật — mọi đường dẫn sẽ báo lỗi 404',
                default => 'Không kiểm tra được từ đây (Nginx hoặc PHP-FPM). Hãy chắc chắn máy chủ có chuyển hướng request vào public/index.php.',
            },
        ];

        $writablePaths = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            // Thư mục gốc phải ghi được thì mới tự tạo được .env và sinh
            // APP_KEY (xem App\\Support\\InstallBootstrapper). Thiếu quyền này
            // thì trước đây người dùng chỉ nhận được lỗi 500 trắng chứ không
            // biết nguyên nhân.
            'thư mục gốc (để tạo .env)' => base_path(),
            // Nơi chứa ảnh tải lên — không ghi được thì Thư viện ảnh hỏng
            // hoàn toàn, nhưng chỉ lộ ra sau khi đã cài xong.
            'content/uploads' => base_path('content/uploads'),
        ];

        foreach ($writablePaths as $label => $path) {
            $checks[] = [
                'label' => "Quyền ghi: {$label}",
                'passed' => is_writable($path),
                'detail' => is_writable($path) ? 'Ghi được' : "Không ghi được ({$path})",
            ];
        }

        return $checks;
    }
}
