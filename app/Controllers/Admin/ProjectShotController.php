<?php

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Session;
use App\Models\Project;
use App\Models\ProjectShot;
use App\Services\Uploader;
use RuntimeException;

/**
 * Proje tanıtım görselleri (sitede /projeler/{slug}/tanitim): yükleme, başlık / açıklama, sıralama, silme.
 */
class ProjectShotController extends AdminController
{
    public function index(string $id): void
    {
        $project = $this->project($id);
        $this->render('projects/showcase', [
            'pageTitle'   => $project->title . ' — Tanıtım Görselleri',
            'breadcrumbs' => ['Projeler' => admin_url('projeler'), $project->title => admin_url('projeler/' . $project->id . '/duzenle'), 'Tanıtım' => null],
            'project'     => $project,
            'shots'       => ProjectShot::forProject((int) $project->id),
        ]);
    }

    /** Birden fazla görsel aynı anda yüklenebilir (input name="gorseller[]") */
    public function upload(string $id): void
    {
        $project = $this->project($id);
        $files = $this->files('gorseller');
        if (!$files) {
            $this->backAdmin('projeler/' . $project->id . '/tanitim', 'Yüklenecek görsel seçilmedi.');
        }
        $uploader = new Uploader();
        $order = (int) ProjectShot::db()->fetchColumn('SELECT IFNULL(MAX(sort_order), 0) FROM project_shots WHERE project_id = ?', [$project->id]);
        $added = 0;
        $errors = [];
        foreach ($files as $file) {
            try {
                $path = $uploader->image($file);
                // Başlık dosya adından önerilir ("02-urun-sayfasi.jpg" -> "Urun sayfasi"); sonradan düzenlenir
                $title = trim(preg_replace('/[-_]+/', ' ', preg_replace('/^\d+[-_ ]*/', '', pathinfo((string) $file['name'], PATHINFO_FILENAME))));
                ProjectShot::create([
                    'project_id' => $project->id,
                    'image'      => $path,
                    'title'      => mb_substr(mb_convert_case($title, MB_CASE_TITLE, 'UTF-8'), 0, 150),
                    'caption'    => null,
                    'sort_order' => ++$order,
                ]);
                $added++;
            } catch (RuntimeException $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
        if ($errors) {
            Session::flash('warning', implode(' · ', $errors));
        }
        if ($added) {
            Session::flash('success', $added . ' tanıtım görseli yüklendi');
        }
        $this->redirectAdmin('projeler/' . $project->id . '/tanitim');
    }

    public function update(string $id, string $shotId): void
    {
        $project = $this->project($id);
        $shot = $this->shot($project, $shotId);
        ProjectShot::updateById((int) $shot->id, [
            'title'   => mb_substr(trim((string) $this->request->post('title', '')), 0, 150),
            'caption' => mb_substr(trim((string) $this->request->post('caption', '')), 0, 1000) ?: null,
        ]);
        $this->respond('Tanıtım görseli güncellendi', admin_url('projeler/' . $project->id . '/tanitim'));
    }

    public function destroy(string $id, string $shotId): void
    {
        $project = $this->project($id);
        $shot = $this->shot($project, $shotId);
        ProjectShot::deleteById((int) $shot->id);
        (new Uploader())->delete((string) $shot->image);   // yalnızca uploads/projects altındaki dosya silinir
        $this->respond('Tanıtım görseli silindi', admin_url('projeler/' . $project->id . '/tanitim'), ['id' => (int) $shot->id]);
    }

    public function reorder(string $id): void
    {
        $project = $this->project($id);
        $ids = array_values(array_unique(array_map('intval', (array) $this->request->post('ids', []))));
        $db = ProjectShot::db();
        $position = 0;
        foreach ($ids as $shotId) {
            // Yalnızca bu projenin görselleri sıralanır
            $db->query('UPDATE project_shots SET sort_order = ? WHERE id = ? AND project_id = ?', [++$position, $shotId, $project->id]);
        }
        Response::success('Tanıtım sırası kaydedildi');
    }

    private function project(string $id): Project
    {
        $project = Project::find((int) $id);
        if (!$project) {
            Session::flash('danger', 'Proje bulunamadı.');
            $this->redirectAdmin('projeler');
        }
        return $project;
    }

    private function shot(Project $project, string $shotId): ProjectShot
    {
        $shot = ProjectShot::find((int) $shotId);
        if (!$shot || (int) $shot->project_id !== (int) $project->id) {
            $this->respond('Görsel bulunamadı.', admin_url('projeler/' . $project->id . '/tanitim'), [], 'danger');
        }
        return $shot;
    }

    /** $_FILES['ad'] çoklu yüklemesini tek tek dosya dizilerine çevirir */
    private function files(string $key): array
    {
        $raw = $_FILES[$key] ?? null;
        if (!$raw || !is_array($raw['name'] ?? null)) {
            return [];
        }
        $out = [];
        foreach ($raw['name'] as $i => $name) {
            if (($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $name, 'type' => $raw['type'][$i], 'tmp_name' => $raw['tmp_name'][$i], 'error' => $raw['error'][$i], 'size' => $raw['size'][$i]];
        }
        return array_slice($out, 0, 20);
    }
}
