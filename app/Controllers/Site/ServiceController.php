<?php

namespace App\Controllers\Site;

use App\Models\Project;
use App\Support\ServiceCatalog;

/** Hizmetler: web sitesi ve masaüstü uygulama yaptırma sayfaları */
class ServiceController extends SiteController
{
    public function index(): void
    {
        $this->render('services', [
            'pageTitle'       => 'Hizmetler — Web Sitesi & Masaüstü Uygulama',
            'metaDescription' => 'Kurumsal web siteleri, e-ticaret, yönetim panelleri ve Windows masaüstü uygulamaları yaptırın. Ücretsiz teklif alın.',
            'process'         => ServiceCatalog::process(),
            'faq'             => ServiceCatalog::faq(),
        ]);
    }

    public function show(string $slug): void
    {
        $service = ServiceCatalog::findBySlug($slug);
        if (!$service) {
            (new PageController($this->request))->notFound();
            return;
        }

        // Örnek çalışmalar: önce paneldeki proje türüne göre (Web Sitesi -> web, Masaüstü -> uygulama);
        // o türde proje yoksa hizmetin dillerine göre
        $kind = ['web' => 'web', 'desktop' => 'app'][$service['key']] ?? null;
        $examples = $kind ? Project::where('is_published = 1 AND kind = ?', [$kind], 'is_featured DESC, sort_order ASC, id DESC', 3) : [];
        $langs = array_values(array_filter((array) ($service['languages'] ?? [])));
        if (!$examples && $langs) {
            $examples = Project::where(
                'is_published = 1 AND language IN (' . implode(',', array_fill(0, count($langs), '?')) . ')',
                $langs,
                'is_featured DESC, sort_order ASC, id DESC',
                3
            );
        }

        $this->render('service-detail', [
            'pageTitle'       => $service['title'],
            'metaDescription' => $service['intro'],
            'service'         => $service,
            'examples'        => $examples,
            'process'         => ServiceCatalog::process(),
            'faq'             => ServiceCatalog::faq(),
        ]);
    }
}
