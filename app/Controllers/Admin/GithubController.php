<?php

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\Project;
use App\Models\Setting;
use App\Services\GithubService;
use RuntimeException;

/**
 * GitHub depolarını listeler, siteye aktarır ve senkronize eder.
 */
class GithubController extends AdminController
{
    private function service(): GithubService
    {
        return new GithubService(Setting::get('github_token') ?: null);
    }

    public function index(): void
    {
        $username = (string) Setting::get('github_username', '');
        $repos = [];
        $error = null;

        if ($username === '') {
            $error = 'Önce Site Ayarları > GitHub bölümünden kullanıcı adınızı girin.';
        } else {
            try {
                $repos = $this->service()->userRepos($username, (bool) $this->request->get('yenile'));
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        $imported = [];
        foreach (Project::all() as $p) {
            if ($p->github_repo) {
                $imported[strtolower((string) $p->github_repo)] = $p;
            }
        }

        $this->render('github/index', [
            'pageTitle'   => 'GitHub Depoları',
            'breadcrumbs' => ['GitHub' => null],
            'username'    => $username,
            'repos'       => $repos,
            'imported'    => $imported,
            'error'       => $error,
        ]);
    }

    /** Seçilen depoları proje olarak ekler */
    public function import(): void
    {
        $selected = $this->request->post('repos', []);
        $selected = is_array($selected) ? $selected : [$selected];
        $publish = $this->request->post('publish') ? 1 : 0;
        $added = 0;
        $errors = [];

        foreach ($selected as $fullName) {
            $fullName = (string) $fullName;
            if (!GithubService::validRepo($fullName) || Project::count('github_repo = ?', [$fullName]) > 0) {
                continue;
            }
            try {
                $repo = $this->service()->repo($fullName);
                $data = GithubService::mapRepo($repo);
                $title = ucwords(str_replace(['-', '_'], ' ', (string) $repo['name']));
                Project::create($data + [
                    'title'        => $title,
                    'slug'         => Project::uniqueSlug((string) $repo['name']),
                    'is_published' => $publish,
                    'show_readme'  => 1,
                ]);
                $added++;
            } catch (RuntimeException $e) {
                $errors[] = $fullName . ': ' . $e->getMessage();
            }
        }

        if ($added) {
            Session::flash('success', $added . ' depo projeler listesine eklendi.');
        }
        foreach ($errors as $err) {
            Session::flash('danger', $err);
        }
        if (!$added && !$errors) {
            Session::flash('info', 'Eklenecek yeni depo seçilmedi.');
        }
        $this->redirectAdmin('github');
    }

    /** GitHub'a bağlı tüm projelerin yıldız/fork/dil bilgisini günceller */
    public function syncAll(): void
    {
        $username = (string) Setting::get('github_username', '');
        try {
            $repos = [];
            foreach ($this->service()->userRepos($username, true) as $repo) {
                $repos[strtolower($repo['full_name'])] = $repo;
            }
            $count = 0;
            foreach (Project::where('github_repo IS NOT NULL') as $project) {
                $repo = $repos[strtolower((string) $project->github_repo)] ?? null;
                if (!$repo) {
                    continue;
                }
                $mapped = GithubService::mapRepo($repo);
                if ($project->summary) unset($mapped['summary']);
                if ($project->tags) unset($mapped['tags']);
                if ($project->demo_url) unset($mapped['demo_url']);
                Project::updateById((int) $project->id, $mapped);
                $count++;
            }
            Session::flash('success', $count . ' proje GitHub ile senkronize edildi.');
        } catch (RuntimeException $e) {
            Session::flash('danger', $e->getMessage());
        }
        $this->redirectAdmin('github');
    }
}
