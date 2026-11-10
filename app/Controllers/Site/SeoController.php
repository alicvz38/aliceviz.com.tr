<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Project;
use App\Support\ServiceCatalog;

/**
 * Arama motorları: robots.txt ve sitemap.xml (veritabanından otomatik oluşur).
 * Panel adresi robots.txt'de yazılmaz (gizli kalsın); panel sayfaları zaten "noindex" etiketlidir.
 */
class SeoController extends SiteController
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /storage/\n";
        echo "Disallow: /install.php\n";
        echo "\n";
        echo 'Sitemap: ' . absolute_url('sitemap.xml') . "\n";
        exit;
    }

    /** ads.txt: AdSense yayıncı kimliği girilmişse yetkili satıcı satırı (reklam kapalıyken de kalır; onay sürecinde gerekir) */
    public function adsTxt(): void
    {
        $client = (string) setting('adsense_client', '');
        if (!preg_match('/^ca-pub-(\d{10,20})$/', $client, $m)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "# ads.txt: AdSense yayıncı kimliği girilmemiş\n";
            exit;
        }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo 'google.com, pub-' . $m[1] . ", DIRECT, f08c47fec0942fa0\n";
        exit;
    }

    public function sitemap(): void
    {
        $urls = [];
        $add = static function (string $path, ?string $lastmod = null, string $freq = 'monthly', string $priority = '0.6') use (&$urls): void {
            $urls[] = [absolute_url($path), $lastmod ? date('Y-m-d', strtotime($lastmod)) : null, $freq, $priority];
        };

        $latest = Database::getInstance()->fetch('SELECT MAX(updated_at) AS t FROM projects WHERE is_published = 1')['t'] ?? null;

        $add('', $latest, 'weekly', '1.0');
        $add('hizmetler', null, 'monthly', '0.9');
        foreach (ServiceCatalog::all() as $service) {
            $add('hizmetler/' . $service['slug'], null, 'monthly', '0.9');
        }
        $add('teklif-al', null, 'yearly', '0.8');
        $add('projeler', $latest, 'weekly', '0.8');
        foreach (Project::published() as $project) {
            $add('projeler/' . $project->slug, $project->updated_at ?: $project->created_at, 'monthly', '0.7');
        }
        $add('hakkimda', null, 'monthly', '0.7');
        $add('iletisim', null, 'yearly', '0.6');

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $lastmod, $freq, $priority]) {
            echo "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
            if ($lastmod) {
                echo "    <lastmod>{$lastmod}</lastmod>\n";
            }
            echo "    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }
        echo "</urlset>\n";
        exit;
    }
}
