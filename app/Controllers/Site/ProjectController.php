<?php

namespace App\Controllers\Site;

use App\Models\Project;
use App\Models\ProjectShot;
use App\Models\Setting;
use App\Services\GithubService;

class ProjectController extends SiteController
{
    private const PER_PAGE = 9;

    public function index(): void
    {
        $language = trim((string) $this->request->get('dil', '')) ?: null;
        $search = trim(mb_substr((string) $this->request->get('q', ''), 0, 80)) ?: null;
        $kind = Project::kindFromSlug((string) $this->request->get('tur', '')); // ?tur=web | ?tur=uygulama
        $total = Project::countPublished($language, $search, $kind);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) $this->request->get('sayfa', 1)));

        $data = [
            'pageTitle' => 'Projeler',
            'metaDescription' => 'Ali Ceviz\'in geliştirdiği web siteleri, yönetim panelleri ve Windows masaüstü uygulamaları: kaynak kodları GitHub\'da açık projeler.',
            // Arama / filtre sonuçları ayrı sayfa olarak dizine eklenmesin (asıl adres /projeler)
            'noindex'   => $search !== null || $language !== null,
            'projects'  => Project::published(self::PER_PAGE, ($page - 1) * self::PER_PAGE, $language, $search, $kind),
            'languages' => Project::languages(),
            'kindCounts' => Project::kindCounts(),
            'language'  => $language,
            'kind'      => $kind,
            'search'    => $search,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
        ];

        // AJAX: yalnızca sonuç bölümünü HTML olarak döndür
        if (\App\Core\Request::isAjax()) {
            $this->json([
                'ok'    => true,
                'html'  => $this->view->partial('project-results', $data + ['kindSlug' => $kind ? Project::KINDS[$kind][2] : '']),
                'total' => $total,
                'page'  => $page,
                'pages' => $pages,
            ]);
        }

        $this->render('projects', $data);
    }

    public function show(string $slug): void
    {
        $project = Project::findPublishedBySlug($slug);
        if (!$project) {
            (new PageController($this->request))->notFound();
            return;
        }

        $readme = null;
        if ($project->show_readme && $project->github_repo) {
            $readme = (new GithubService(Setting::get('github_token') ?: null))->readmeHtml((string) $project->github_repo);
        }

        $related = $project->language
            ? Project::where('is_published = 1 AND language = ? AND id <> ?', [$project->language, $project->id], 'RAND()', 3)
            : [];

        $this->render('project-detail', [
            'shots'     => ProjectShot::forProject((int) $project->id),
            'pageTitle' => $project->title,
            'metaDescription' => $project->summary,
            'ogImage'   => $project->imageUrl(),
            'ogType'    => 'article',
            'schema'    => [
                '@type'               => $project->kind === 'web' ? 'WebSite' : 'SoftwareApplication',
                'name'                => $project->title,
                'description'         => (string) $project->summary,
                'url'                 => absolute_url('projeler/' . $project->slug),
                'image'               => $project->imageUrl() ? absolute_url($project->imageUrl()) : null,
                'author'              => ['@id' => absolute_url() . '#kisi'],
                'programmingLanguage' => $project->language ?: null,
                'codeRepository'      => $project->github_url ?: null,
                'applicationCategory' => $project->kind === 'web' ? null : 'DeveloperApplication',
                'operatingSystem'     => $project->kind === 'app' ? 'Windows' : null,
            ],
            'project'   => $project,
            'readme'    => $readme,
            'related'   => $related,
        ]);
    }
    /** Tanıtım sayfası: projenin ekran görüntüleri sırayla, başlık ve açıklamalarıyla */
    public function showcase(string $slug): void
    {
        $project = Project::findPublishedBySlug($slug);
        $shots = $project ? ProjectShot::forProject((int) $project->id) : [];
        if (!$project || !$shots) {
            (new PageController($this->request))->notFound();
            return;
        }
        $this->render('project-showcase', [
            'pageTitle'       => $project->title . ' — Tanıtım',
            'metaDescription' => $project->title . ' ekran görüntüleriyle: ' . ((string) $project->summary ?: 'özellikler ve arayüz.'),
            'ogImage'         => $shots[0]->imageUrl(),
            'project'         => $project,
            'shots'           => $shots,
        ]);
    }
}
