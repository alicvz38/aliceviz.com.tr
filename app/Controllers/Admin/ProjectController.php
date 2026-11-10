<?php

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\Project;
use App\Models\Setting;
use App\Services\GithubService;
use App\Services\Uploader;
use RuntimeException;

class ProjectController extends AdminController
{
    public function index(): void
    {
        $this->render('projects/index', [
            'pageTitle'   => 'Projeler',
            'breadcrumbs' => ['Projeler' => null],
            'projects'    => Project::all('sort_order ASC, id DESC'),
        ]);
    }

    public function create(): void
    {
        $this->render('projects/form', [
            'pageTitle'   => 'Yeni Proje',
            'breadcrumbs' => ['Projeler' => admin_url('projeler'), 'Yeni Proje' => null],
            'project'     => new Project(['is_published' => 1, 'show_readme' => 1, 'sort_order' => 0]),
        ]);
    }

    public function store(): void
    {
        $data = $this->validated(null, 'projeler/ekle');

        if ($file = $this->request->file('image')) {
            try {
                $data['image'] = (new Uploader())->image($file);
            } catch (RuntimeException $e) {
                $this->backAdmin('projeler/ekle', $e->getMessage());
            }
        }

        $id = Project::create($data);
        // Ekledikten sonra düzenleme sayfasına geç (AJAX'ta da aynı adrese yönlendirilir)
        $this->respond('"' . $data['title'] . '" projesi eklendi.', admin_url('projeler/' . $id . '/duzenle'), ['id' => $id]);
    }

    public function edit(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        $this->render('projects/form', [
            'pageTitle'   => 'Projeyi Düzenle',
            'breadcrumbs' => ['Projeler' => admin_url('projeler'), $project->title => null],
            'project'     => $project,
        ]);
    }

    public function update(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        $back = 'projeler/' . $project->id . '/duzenle';
        $data = $this->validated($project, $back);
        $uploader = new Uploader();

        if ($this->request->post('remove_image')) {
            $uploader->delete($project->image);
            $data['image'] = null;
        }
        if ($file = $this->request->file('image')) {
            try {
                $data['image'] = $uploader->image($file);
                $uploader->delete($project->image);
            } catch (RuntimeException $e) {
                $this->backAdmin($back, $e->getMessage());
            }
        }

        Project::updateById((int) $project->id, $data);
        $fresh = Project::find((int) $project->id);
        $this->respond('Proje güncellendi.', admin_url($back), [
            'slug'  => $fresh->slug,
            'image' => $fresh->imageUrl(),
        ]);
    }

    public function destroy(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        (new Uploader())->delete($project->image);
        Project::deleteById((int) $project->id);
        Session::flash('success', '"' . $project->title . '" silindi.');
        $this->redirectAdmin('projeler');
    }

    /** Yayında / taslak arasında geçiş */
    public function toggle(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        $published = $project->is_published ? 0 : 1;
        Project::updateById((int) $project->id, ['is_published' => $published]);
        $this->respond($published ? 'Proje yayınlandı.' : 'Proje taslağa alındı.', admin_url('projeler'), [
            'published' => (bool) $published,
        ]);
    }

    /** Proje türünü (Web / Uygulama / belirtilmemiş) listeden hızlıca değiştirir */
    public function setKind(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        $kind = (string) $this->request->post('kind', '');
        $kind = isset(Project::KINDS[$kind]) ? $kind : null;
        Project::updateById((int) $project->id, ['kind' => $kind]);
        $label = $kind ? Project::KINDS[$kind][0] : 'Belirtilmemiş';
        $this->respond('"' . $project->title . '" türü: ' . $label, admin_url('projeler'), ['kind' => $kind]);
    }

    /** GitHub'dan yıldız, fork, dil vb. bilgileri günceller */
    public function sync(string $id): void
    {
        $project = $this->findOrFail((int) $id);
        if (!$project->github_repo) {
            Session::flash('warning', 'Bu proje bir GitHub deposuna bağlı değil.');
            $this->redirectAdmin('projeler');
        }
        try {
            $repo = (new GithubService(Setting::get('github_token') ?: null))->repo((string) $project->github_repo);
            $mapped = GithubService::mapRepo($repo);
            // Elle yazılmış açıklama, etiketler ve dil korunur (GitHub dili boş/yanlış bildirebilir)
            if ($project->summary) unset($mapped['summary']);
            if ($project->tags) unset($mapped['tags']);
            if ($project->demo_url) unset($mapped['demo_url']);
            if ($project->language) unset($mapped['language']);
            Project::updateById((int) $project->id, $mapped);
            $fresh = Project::find((int) $project->id);
            $this->respond('GitHub bilgileri güncellendi.', admin_url('projeler'), [
                'stars'    => (int) $fresh->stars,
                'forks'    => (int) $fresh->forks,
                'language' => $fresh->language,
            ]);
        } catch (RuntimeException $e) {
            $this->respond($e->getMessage(), admin_url('projeler'), [], 'danger');
        }
    }

    private function findOrFail(int $id): Project
    {
        $project = Project::find($id);
        if (!$project) {
            Session::flash('danger', 'Proje bulunamadı.');
            $this->redirectAdmin('projeler');
        }
        return $project;
    }

    /** Form verisini doğrular ve temizler */
    private function validated(?Project $project, string $back): array
    {
        $r = $this->request;
        $title = (string) $r->post('title', '');

        if ($title === '' || mb_strlen($title) > 150) {
            $this->backAdmin($back, 'Proje adı zorunludur (en fazla 150 karakter).');
        }

        foreach (['github_url' => 'GitHub adresi', 'demo_url' => 'Demo adresi'] as $field => $label) {
            $value = (string) $r->post($field, '');
            if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value))) {
                $this->backAdmin($back, $label . ' geçerli bir http(s) bağlantısı olmalıdır.');
            }
        }

        $repo = (string) $r->post('github_repo', '');
        if ($repo !== '' && !GithubService::validRepo($repo)) {
            $this->backAdmin($back, 'GitHub deposu "kullanici/depo-adi" biçiminde olmalıdır.');
        }
        if ($repo !== '') {
            $params = [$repo];
            $where = 'github_repo = ?';
            if ($project) {
                $where .= ' AND id <> ?';
                $params[] = $project->id;
            }
            if (Project::count($where, $params) > 0) {
                $this->backAdmin($back, 'Bu GitHub deposu zaten başka bir projeye bağlı.');
            }
        }

        $slugInput = (string) $r->post('slug', '');
        $slug = Project::uniqueSlug($slugInput !== '' ? $slugInput : $title, $project?->id ? (int) $project->id : null);

        return [
            'title'        => $title,
            'slug'         => $slug,
            'summary'      => mb_substr((string) $r->post('summary', ''), 0, 500) ?: null,
            'content'      => (string) $r->post('content', '') ?: null,
            'github_repo'  => $repo ?: null,
            'github_url'   => (string) $r->post('github_url', '') ?: ($repo ? 'https://github.com/' . $repo : null),
            'demo_url'     => (string) $r->post('demo_url', '') ?: null,
            'language'     => mb_substr((string) $r->post('language', ''), 0, 50) ?: null,
            'kind'         => isset(Project::KINDS[(string) $r->post('kind', '')]) ? (string) $r->post('kind') : null,
            'tags'         => mb_substr((string) $r->post('tags', ''), 0, 255) ?: null,
            'stars'        => max(0, (int) $r->post('stars', 0)),
            'forks'        => max(0, (int) $r->post('forks', 0)),
            'sort_order'   => (int) $r->post('sort_order', 0),
            'is_featured'  => $r->post('is_featured') ? 1 : 0,
            'is_published' => $r->post('is_published') ? 1 : 0,
            'show_readme'  => $r->post('show_readme') ? 1 : 0,
        ];
    }
}
