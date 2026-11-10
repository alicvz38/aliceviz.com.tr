<?php

namespace App\Services;

use App\Core\Cache;
use App\Core\Config;
use RuntimeException;

/**
 * GitHub REST API istemcisi. Yanıtlar önbelleğe alınır.
 * Token zorunlu değildir; token olmadan saatte 60 istek sınırı vardır.
 */
class GithubService
{
    private const API = 'https://api.github.com';

    private Cache $cache;
    private int $ttl;

    public function __construct(private ?string $token = null, ?Cache $cache = null)
    {
        $this->cache = $cache ?? new Cache();
        $this->ttl = (int) Config::get('github.cache_ttl', 3600);
    }

    public static function validUsername(string $username): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $username);
    }

    public static function validRepo(string $fullName): bool
    {
        return (bool) preg_match('#^[A-Za-z0-9-]{1,39}/[A-Za-z0-9._-]{1,100}$#', $fullName);
    }

    /** Kullanıcının herkese açık depoları (en son güncellenene göre) */
    public function userRepos(string $username, bool $fresh = false): array
    {
        if (!self::validUsername($username)) {
            throw new RuntimeException('Geçersiz GitHub kullanıcı adı.');
        }
        $key = 'gh_repos_' . strtolower($username);
        if ($fresh) {
            $this->cache->forget($key);
        }
        return $this->cache->remember($key, $this->ttl, function () use ($username) {
            $repos = [];
            for ($page = 1; $page <= 5; $page++) {
                $batch = $this->json("/users/{$username}/repos?per_page=100&sort=updated&page={$page}");
                $repos = array_merge($repos, $batch);
                if (count($batch) < 100) {
                    break;
                }
            }
            return $repos;
        }) ?? [];
    }

    public function repo(string $fullName): array
    {
        if (!self::validRepo($fullName)) {
            throw new RuntimeException('Geçersiz depo adı.');
        }
        return $this->json('/repos/' . $fullName);
    }

    /** README dosyasını GitHub'ın işlediği HTML olarak döndürür */
    public function readmeHtml(string $fullName): ?string
    {
        if (!self::validRepo($fullName)) {
            return null;
        }
        // README yoksa boş metin önbelleğe alınır; böylece her ziyarette tekrar istek atılmaz
        $html = $this->cache->remember('gh_readme_' . strtolower($fullName), $this->ttl, function () use ($fullName) {
            try {
                return $this->request('/repos/' . $fullName . '/readme', 'application/vnd.github.html+json');
            } catch (RuntimeException) {
                return '';
            }
        });
        return $html !== '' ? $html : null;
    }

    /** GitHub depo verisini proje alanlarına dönüştürür */
    public static function mapRepo(array $repo): array
    {
        $topics = $repo['topics'] ?? [];
        return [
            'github_repo' => $repo['full_name'],
            'github_url'  => $repo['html_url'],
            'demo_url'    => $repo['homepage'] ?: null,
            'summary'     => $repo['description'] ?? null,
            'language'    => $repo['language'] ?? null,
            'stars'       => (int) ($repo['stargazers_count'] ?? 0),
            'forks'       => (int) ($repo['forks_count'] ?? 0),
            'tags'        => $topics ? implode(', ', $topics) : null,
            'pushed_at'   => isset($repo['pushed_at']) ? date('Y-m-d H:i:s', strtotime($repo['pushed_at'])) : null,
        ];
    }

    /** Depodaki commit'ler (en yeni önce, en fazla 100) */
    public function commits(string $fullName, ?string $since = null): array
    {
        if (!self::validRepo($fullName)) {
            return [];
        }
        return $this->json('/repos/' . $fullName . '/commits?per_page=100' . ($since ? '&since=' . rawurlencode($since) : ''));
    }

    private function json(string $endpoint): array
    {
        $data = json_decode($this->request($endpoint), true);
        if (!is_array($data)) {
            throw new RuntimeException('GitHub yanıtı çözümlenemedi.');
        }
        return $data;
    }

    private function request(string $endpoint, string $accept = 'application/vnd.github+json'): string
    {
        $headers = [
            'Accept: ' . $accept,
            'User-Agent: ' . Config::get('github.user_agent', 'php-portfolio'),
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init(self::API . $endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            if ($body === false) {
                throw new RuntimeException('GitHub bağlantı hatası: ' . $error);
            }
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 15, 'ignore_errors' => true,
            ]]);
            $body = @file_get_contents(self::API . $endpoint, false, $context);
            $status = 0;
            foreach ($http_response_header ?? [] as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                    $status = (int) $m[1];
                }
            }
            if ($body === false) {
                throw new RuntimeException('GitHub bağlantı hatası.');
            }
        }

        if ($status === 404) {
            throw new RuntimeException('GitHub üzerinde bulunamadı.');
        }
        if ($status === 403 || $status === 429) {
            throw new RuntimeException('GitHub API istek sınırına ulaşıldı. Ayarlardan bir token ekleyebilir veya biraz bekleyebilirsiniz.');
        }
        if ($status >= 400) {
            throw new RuntimeException('GitHub API hatası (HTTP ' . $status . ').');
        }
        return (string) $body;
    }
}
