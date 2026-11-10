<?php

namespace App\Controllers\Site;

use App\Models\Project;
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
            'pageTitle' => $project->title,
            'metaDescription' => $project->summary,
            'project'   => $project,
            'readme'    => $readme,
            'related'   => $related,
        ]);
    }
}
