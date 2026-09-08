<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Real database + uploaded-media backups, stored locally on the same
 * server (no S3/off-site target configured yet — see [[project_mamxanhdigital_cms]]
 * for the tradeoff). Retention keeps only the last KEEP_COUNT archives so
 * disk usage doesn't grow unbounded.
 */
class BackupService
{
    private const KEEP_COUNT = 7;

    public function run(): array
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stamp = date('Y-m-d_His');
        $sqlPath = "{$dir}/db-{$stamp}.sql";
        $zipPath = "{$dir}/backup-{$stamp}.zip";
        $start = microtime(true);

        try {
            $this->dumpDatabase($sqlPath);
            $this->zipUp($zipPath, $sqlPath);
            @unlink($sqlPath);
            $this->pruneOld($dir);

            $result = [
                'success' => true,
                'path' => $zipPath,
                'sizeBytes' => filesize($zipPath) ?: 0,
                'durationSeconds' => round(microtime(true) - $start, 1),
            ];
        } catch (\Throwable $e) {
            @unlink($sqlPath);
            @unlink($zipPath);
            Log::error('Backup failed: '.$e->getMessage());

            $result = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Reads the backups directory directly rather than a DB log table —
     * the files on disk are the source of truth, so there's nothing to
     * fall out of sync.
     */
    public function status(): array
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            return ['count' => 0, 'latest' => null, 'latestAt' => null, 'sizeBytes' => null];
        }

        $files = collect(glob("{$dir}/backup-*.zip") ?: [])
            ->sortByDesc(fn (string $f) => filemtime($f))
            ->values();

        $latest = $files->first();

        return [
            'count' => $files->count(),
            'latest' => $latest ? basename($latest) : null,
            'latestAt' => $latest ? \Illuminate\Support\Carbon::createFromTimestamp(filemtime($latest)) : null,
            'sizeBytes' => $latest ? filesize($latest) : null,
        ];
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new \RuntimeException('Chỉ hỗ trợ sao lưu MySQL ở phiên bản này (driver hiện tại: '.($config['driver'] ?? 'unknown').').');
        }

        $binary = $this->resolveMysqldumpBinary();

        $process = new Process([
            $binary,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--skip-lock-tables',
            '--result-file='.$sqlPath,
            $config['database'],
        ], null, ['MYSQL_PWD' => $config['password']]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump thất bại: '.mb_substr($process->getErrorOutput(), 0, 300));
        }

        if (! file_exists($sqlPath) || filesize($sqlPath) === 0) {
            throw new \RuntimeException('mysqldump chạy xong nhưng không tạo ra file dữ liệu.');
        }
    }

    private function resolveMysqldumpBinary(): string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/Applications/ServBay/bin/mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // Bare command name — let the shell resolve it via PATH.
        $check = Process::fromShellCommandline('command -v mysqldump');
        $check->run();

        if ($check->isSuccessful() && trim($check->getOutput()) !== '') {
            return 'mysqldump';
        }

        throw new \RuntimeException('Không tìm thấy lệnh mysqldump trên server. Đặt biến MYSQLDUMP_PATH trong .env nếu đường dẫn khác thường.');
    }

    private function zipUp(string $zipPath, string $sqlPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Không tạo được file zip.');
        }

        $zip->addFile($sqlPath, 'database.sql');

        // Đọc gốc đĩa từ config chứ không viết cứng đường dẫn: ảnh đã
        // chuyển từ storage/app/public sang content/uploads, và bản sao lưu
        // im lặng bỏ sót toàn bộ ảnh là kiểu hỏng tệ nhất — chỉ phát hiện ra
        // đúng lúc cần khôi phục.
        $mediaDir = config('filesystems.disks.public.root');

        if (is_dir($mediaDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($mediaDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relative = 'media/'.substr($file->getPathname(), strlen($mediaDir) + 1);
                    $zip->addFile($file->getPathname(), $relative);
                }
            }
        }

        $zip->close();
    }

    private function pruneOld(string $dir): void
    {
        $files = collect(glob("{$dir}/backup-*.zip") ?: [])
            ->sortByDesc(fn (string $f) => filemtime($f))
            ->values();

        foreach ($files->slice(self::KEEP_COUNT) as $old) {
            @unlink($old);
        }
    }
}
