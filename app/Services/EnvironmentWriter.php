<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Updates key=value pairs in the project's .env file. Used by the
 * installation wizard (see App\Http\Controllers\Install) to persist the
 * database and site configuration the user submits, since Laravel doesn't
 * write to .env at runtime by itself.
 */
class EnvironmentWriter
{
    public function __construct(private readonly string $envPath = '')
    {
    }

    private function path(): string
    {
        return $this->envPath !== '' ? $this->envPath : base_path('.env');
    }

    /**
     * @param  array<string, string>  $values
     */
    public function set(array $values): void
    {
        $path = $this->path();
        $contents = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $contents = $this->setKey($contents, $key, $value);
        }

        file_put_contents($path, $contents);
    }

    private function setKey(string $contents, string $key, string $value): string
    {
        $escaped = $this->escapeValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            return preg_replace($pattern, "{$key}={$escaped}", $contents);
        }

        return rtrim($contents)."\n{$key}={$escaped}\n";
    }

    private function escapeValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|"/', $value) === 1) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
