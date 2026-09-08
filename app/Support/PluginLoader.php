<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Nạp plugin của người dùng từ content/plugins/*\/plugin.php.
 *
 * Hệ thống hook (add_action/do_action/add_filter trong app/helpers.php) đã
 * tồn tại từ đầu nhưng chưa từng có ai nạp plugin từ đĩa cả — nghĩa là lời
 * hứa "mở rộng bằng plugin" vẫn chưa có thật. Lớp này làm nó thành thật.
 *
 * Điểm khác WordPress một cách CÓ CHỦ ĐÍCH: một plugin ném exception ở đây
 * sẽ bị bỏ qua và ghi log, chứ không làm trắng cả website. WordPress nổi
 * tiếng với cảnh "màn hình trắng chết chóc" khi một plugin lỗi; không có lý
 * do gì phải lặp lại sai lầm đó.
 */
class PluginLoader
{
    /** @return array<int, string> slug của các plugin đã nạp thành công */
    public static function boot(): array
    {
        // Công tắc thoát hiểm: người dùng bị plugin làm hỏng site vẫn vào
        // được admin bằng cách thêm 1 dòng vào .env, không cần xoá file.
        if (! filter_var(env('MXD_PLUGINS', true), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $loaded = [];

        foreach (glob(base_path('content/plugins/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            $entry = $dir.'/plugin.php';

            if (! is_file($entry)) {
                continue;
            }

            try {
                require_once $entry;
                $loaded[] = basename($dir);
            } catch (\Throwable $e) {
                // Ghi lại đủ để sửa được, nhưng không cho lỗi lan ra request.
                Log::error('Plugin lỗi, đã bỏ qua: '.basename($dir).' — '.$e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $loaded;
    }
}
