<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Encryption\Encrypter;

/**
 * Làm cho bản cài mới chạy được ngay sau khi `git pull` mà không cần SSH.
 *
 * Vấn đề thật đã tái hiện được: clone sạch từ GitHub (không có .env) rồi mở
 * /install thì Laravel trả 500 chứ không hiện được trang cài đặt — vì
 * middleware EncryptCookies cần APP_KEY, mà APP_KEY chỉ có sau khi chạy
 * `php artisan key:generate` bằng dòng lệnh. Đúng thứ mà người dùng hosting
 * phổ thông (chỉ có FTP/File Manager) không làm được.
 *
 * Lớp này chạy trong register() của AppServiceProvider — tức TRƯỚC mọi
 * middleware — và chỉ hoạt động khi site CHƯA cài đặt. Sau khi có
 * install.lock thì nó thoát ngay ở dòng đầu, không đụng vào đĩa nữa.
 */
class InstallBootstrapper
{
    public static function ensureAppKey(): void
    {
        // Đã cài xong thì tuyệt đối không tự ghi .env nữa — quá trình vận
        // hành bình thường không bao giờ được sửa file cấu hình.
        if (file_exists(storage_path('app/install.lock'))) {
            return;
        }

        if (filled(config('app.key'))) {
            return;
        }

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        // Chưa có .env thì dựng từ .env.example. Không có example luôn thì
        // vẫn tạo file rỗng để còn ghi được APP_KEY vào.
        $justCreated = false;

        if (! file_exists($envPath)) {
            if (! is_writable(base_path())) {
                return;
            }

            @copy($examplePath, $envPath) || @file_put_contents($envPath, '');
            $justCreated = file_exists($envPath);
        }

        $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));

        // Đặt vào config trước: kể cả khi ghi .env thất bại (host cấm ghi),
        // request hiện tại vẫn chạy được để installer hiện ra thông báo rõ
        // ràng thay vì màn hình 500 trắng.
        config(['app.key' => $key]);

        if (is_writable($envPath)) {
            (new \App\Services\EnvironmentWriter)->set(['APP_KEY' => $key]);
        }

        // Vừa TẠO MỚI .env thì phải nạp lại toàn bộ tiến trình, không thể
        // chữa cháy trong request này.
        //
        // Lý do (đã tái hiện thật): config của Laravel được nạp ở bước
        // LoadConfiguration, chạy TRƯỚC khi provider register(). Ở request
        // đầu tiên của một bản giải nén sạch thì chưa có .env, nên
        // config('database.default') đã bị "đóng băng" thành sqlite (mặc
        // định của Laravel 11+) — dù ngay sau đó ta ghi ra .env với
        // DB_CONNECTION=mysql. Kết quả: người dùng mở website lần đầu là
        // gặp ngay lỗi 500 "Database file ... does not exist", chỉ hết sau
        // khi bấm tải lại. Một CMS chào người dùng bằng màn hình lỗi ở lần
        // truy cập đầu tiên thì coi như hỏng.
        //
        // Chuyển hướng về đúng URL hiện tại là cách chắc chắn nhất: tiến
        // trình sau khởi động lại với .env đầy đủ. Không có nguy cơ lặp vô
        // hạn vì nhánh này chỉ chạy đúng lúc .env chuyển từ "chưa có" sang
        // "đã có".
        if ($justCreated && PHP_SAPI !== 'cli' && ! headers_sent()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: '.$uri, true, 302);
            header('Cache-Control: no-store');
            exit;
        }
    }
}
