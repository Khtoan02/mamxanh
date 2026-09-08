<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\BackupService;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Sao lưu database + thư mục media vào storage/app/backups (giữ 7 bản gần nhất)';

    public function handle(BackupService $backups): int
    {
        $this->info('Đang sao lưu...');

        $result = $backups->run();

        if ($result['success']) {
            $this->info(sprintf(
                'Xong: %s (%s, %ss)',
                basename($result['path']),
                number_format($result['sizeBytes'] / 1_048_576, 1).' MB',
                $result['durationSeconds']
            ));

            return self::SUCCESS;
        }

        $this->error('Sao lưu thất bại: '.$result['error']);

        return self::FAILURE;
    }
}
