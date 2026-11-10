<?php

namespace App\Core;

/**
 * Dosya tabanlı basit önbellek (storage/cache).
 */
final class Cache
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? STORAGE_PATH . '/cache';
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . sha1($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }
        $payload = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        if (!is_array($payload) || $payload['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $payload['value'];
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        file_put_contents($this->file($key), serialize(['expires' => time() + $ttl, 'value' => $value]), LOCK_EX);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }
        return $value;
    }

    public function forget(string $key): void
    {
        @unlink($this->file($key));
    }

    public function clear(): int
    {
        $count = 0;
        foreach (glob($this->dir . '/*.cache') ?: [] as $file) {
            $count += (int) @unlink($file);
        }
        return $count;
    }
}
