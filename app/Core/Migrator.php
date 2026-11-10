<?php

namespace App\Core;

/**
 * Basit veritabanı güncelleme (migration) sistemi.
 * database/migrations/*.sql dosyaları ada göre sırayla, yalnızca bir kez çalıştırılır.
 * Çalıştırılanlar `migrations` tablosunda ve hız için storage/migrations.json'da tutulur.
 * Yeni bir tablo/alan eklemek için: database/migrations/00X_aciklama.sql oluşturun.
 */
final class Migrator
{
    private string $dir;
    private string $stateFile;

    public function __construct()
    {
        $this->dir = ROOT_PATH . '/database/migrations';
        $this->stateFile = STORAGE_PATH . '/migrations.json';
    }

    /** @return string[] Bekleyen dosya adları */
    public function pending(): array
    {
        $files = array_map('basename', glob($this->dir . '/*.sql') ?: []);
        sort($files);
        $done = is_file($this->stateFile) ? (json_decode((string) file_get_contents($this->stateFile), true) ?: []) : [];
        return array_values(array_diff($files, $done));
    }

    /** Bekleyenleri çalıştırır; istek başına yalnızca dosya listesi karşılaştırılır (DB'ye dokunmaz) */
    public function run(): int
    {
        $pending = $this->pending();
        if (!$pending) {
            return 0;
        }

        $pdo = Database::getInstance()->pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS `migrations` (
            `name` VARCHAR(190) NOT NULL PRIMARY KEY,
            `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $ran = array_column($pdo->query('SELECT name FROM migrations')->fetchAll(\PDO::FETCH_ASSOC), 'name');
        $count = 0;

        foreach ($pending as $file) {
            if (!in_array($file, $ran, true)) {
                $sql = (string) file_get_contents($this->dir . '/' . $file);
                $sql = preg_replace('/^\s*--.*$/m', '', $sql);
                foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $statement) {
                    $pdo->exec($statement);
                }
                $pdo->prepare('INSERT INTO migrations (name) VALUES (?)')->execute([$file]);
                $count++;
            }
            $ran[] = $file;
        }

        @file_put_contents($this->stateFile, json_encode(array_values(array_unique($ran))));
        return $count;
    }
}
