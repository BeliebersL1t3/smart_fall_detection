<?php

namespace App\Support;

use RuntimeException;

class EnvFileWriter
{
    public function path(): string
    {
        return base_path('.env');
    }

    public function get(string $key, string $default = ''): string
    {
        $value = env($key);

        return $value !== null && $value !== '' ? (string) $value : $default;
    }

    /**
     * @param  array<string, string|null>  $pairs
     */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set(string $key, ?string $value): void
    {
        $path = $this->path();

        if (! is_file($path) || ! is_writable($path)) {
            throw new RuntimeException('File .env tidak ditemukan atau tidak bisa ditulis.');
        }

        $content = file_get_contents($path);
        $line = $key.'='.$this->escapeValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $line, $content);
        } else {
            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $content);
    }

    private function escapeValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (preg_match('/[\s#"$\\\\]/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
