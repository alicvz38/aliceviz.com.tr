<?php

namespace App\Models;

use App\Core\Model;

/**
 * GitHub projeleri.
 */
class Project extends Model
{
    protected static string $table = 'projects';
    protected static array $fillable = [
        'title', 'slug', 'summary', 'content', 'github_repo', 'github_url', 'demo_url',
        'language', 'kind', 'tags', 'stars', 'forks', 'image', 'is_featured', 'is_published',
        'show_readme', 'sort_order', 'pushed_at', 'updated_at',
    ];

    /** Yaygın dillerin GitHub renkleri */
    private const LANGUAGE_COLORS = [
        'PHP' => '#4F5D95', 'JavaScript' => '#f1e05a', 'TypeScript' => '#3178c6', 'Python' => '#3572A5',
        'HTML' => '#e34c26', 'CSS' => '#663399', 'SCSS' => '#c6538c', 'C#' => '#178600', 'C++' => '#f34b7d',
        'C' => '#555555', 'Java' => '#b07219', 'Kotlin' => '#A97BFF', 'Swift' => '#F05138', 'Go' => '#00ADD8',
        'Rust' => '#dea584', 'Dart' => '#00B4AB', 'Vue' => '#41b883', 'Shell' => '#89e051', 'Ruby' => '#701516',
        'Jupyter Notebook' => '#DA5B0B', 'Blade' => '#f7523f',
    ];

    /**
     * Proje türleri: anahtar => [etiket, ikon, sitedeki filtre adresi (?tur=)]
     * Panelde her proje için seçilir; seçilmemişse türsüz kalır.
     */
    public const KINDS = [
        'web' => ['Web', 'globe', 'web'],
        'app' => ['Uygulama', 'monitor', 'uygulama'],
    ];

    /** Sitedeki ?tur=uygulama gibi değeri iç anahtara çevirir */
    public static function kindFromSlug(?string $slug): ?string
    {
        foreach (self::KINDS as $key => [, , $s]) {
            if ($slug === $s) {
                return $key;
            }
        }
        return null;
    }

    // ---------- Sorgular ----------

    /** @return self[] */
    public static function published(int $limit = 0, int $offset = 0, ?string $language = null, ?string $search = null, ?string $kind = null): array
    {
        [$where, $params] = self::publicFilter($language, $search, $kind);
        return static::where($where, $params, 'sort_order ASC, is_featured DESC, id DESC', $limit ?: null, $offset);
    }

    public static function countPublished(?string $language = null, ?string $search = null, ?string $kind = null): int
    {
        [$where, $params] = self::publicFilter($language, $search, $kind);
        return static::count($where, $params);
    }

    /** Yayındaki projelerin türlere göre sayısı: ['web' => 3, 'app' => 2] */
    public static function kindCounts(): array
    {
        $out = array_fill_keys(array_keys(self::KINDS), 0);
        foreach (static::db()->fetchAll('SELECT kind, COUNT(*) AS n FROM projects WHERE is_published = 1 AND kind IS NOT NULL GROUP BY kind') as $r) {
            if (isset($out[$r['kind']])) {
                $out[$r['kind']] = (int) $r['n'];
            }
        }
        return $out;
    }

    private static function publicFilter(?string $language, ?string $search, ?string $kind = null): array
    {
        $where = 'is_published = 1';
        $params = [];
        if ($kind && isset(self::KINDS[$kind])) {
            $where .= ' AND kind = ?';
            $params[] = $kind;
        }
        if ($language) {
            $where .= ' AND language = ?';
            $params[] = $language;
        }
        if ($search) {
            $where .= ' AND (title LIKE ? OR summary LIKE ? OR tags LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }
        return [$where, $params];
    }

    /** @return self[] */
    public static function featured(int $limit = 6): array
    {
        return static::where('is_published = 1 AND is_featured = 1', [], 'sort_order ASC, id DESC', $limit);
    }

    public static function findPublishedBySlug(string $slug): ?self
    {
        $items = static::where('slug = ? AND is_published = 1', [$slug], 'id DESC', 1);
        return $items[0] ?? null;
    }

    public static function languages(): array
    {
        return array_column(static::db()->fetchAll(
            'SELECT language, COUNT(*) AS total FROM projects WHERE is_published = 1 AND language IS NOT NULL AND language <> \'\' GROUP BY language ORDER BY total DESC'
        ), 'total', 'language');
    }

    public static function totalStars(): int
    {
        return (int) static::db()->fetchColumn('SELECT COALESCE(SUM(stars),0) FROM projects WHERE is_published = 1');
    }

    /** Benzersiz slug üretir */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = str_slug($title);
        $slug = $base;
        $i = 2;
        while (true) {
            $params = [$slug];
            $sql = 'slug = ?';
            if ($ignoreId) {
                $sql .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            if (static::count($sql, $params) === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    // ---------- Nesne yardımcıları ----------

    public function url(): string
    {
        return url('projeler/' . $this->slug);
    }

    /** @return string[] */
    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }

    public function imageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return preg_match('#^https?://#', (string) $this->image) ? (string) $this->image : url((string) $this->image);
    }

    public function languageColor(): string
    {
        return self::LANGUAGE_COLORS[(string) $this->language] ?? '#38bdf8';
    }

    public function kindLabel(): ?string
    {
        return self::KINDS[(string) $this->kind][0] ?? null;
    }

    public function kindIcon(): string
    {
        return self::KINDS[(string) $this->kind][1] ?? 'code';
    }

    public function isFromGithub(): bool
    {
        return !empty($this->github_repo);
    }
}
