<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\ResumeItem;
use App\Models\Setting;
use App\Services\Uploader;
use RuntimeException;

/**
 * Özgeçmiş (CV) yönetimi: profil bilgileri + deneyim, eğitim, sertifika, yetenek, dil kalemleri.
 * Tüm işlemler AJAX ile (modal form) yapılır; JS kapalıysa klasik form olarak da çalışır.
 */
class ResumeController extends AdminController
{
    public function index(): void
    {
        $this->render('resume/index', [
            'pageTitle'   => 'Özgeçmiş (CV)',
            'breadcrumbs' => ['Özgeçmiş' => null],
            'items'       => ResumeItem::grouped(),
            'settings'    => Setting::allSettings(),
        ]);
    }

    /** Profil kartı: unvan, konum, telefon, fotoğraf, hakkımda yazısı */
    public function profile(): void
    {
        $r = $this->request;
        $values = [
            'cv_title'     => mb_substr((string) $r->post('cv_title', ''), 0, 150),
            'cv_location'  => mb_substr((string) $r->post('cv_location', ''), 0, 100),
            'cv_phone'     => mb_substr((string) $r->post('cv_phone', ''), 0, 30),
            'cv_available' => $r->post('cv_available') ? '1' : '0',
            'about_text'   => mb_substr((string) $r->post('about_text', ''), 0, 3000),
        ];

        $phone = phone_normalize($values['cv_phone']);
        if ($phone === false) {
            $this->backAdmin('ozgecmis', 'Telefon numarası geçersiz.', ['cv_phone' => '0 (5XX) XXX XX XX biçiminde eksiksiz girin.']);
        }
        $values['cv_phone'] = (string) $phone;

        $uploader = new Uploader();
        $avatar = (string) Setting::get('cv_avatar', '');
        if ($r->post('remove_avatar')) {
            $uploader->delete($avatar);
            $avatar = '';
        }
        if ($file = $r->file('avatar')) {
            try {
                $new = $uploader->image($file);
                $uploader->delete($avatar);
                $avatar = $new;
            } catch (RuntimeException $e) {
                $this->backAdmin('ozgecmis', $e->getMessage(), ['avatar' => $e->getMessage()]);
            }
        }
        $values['cv_avatar'] = $avatar;

        Setting::setMany($values);
        $this->respond('Profil bilgileri kaydedildi.', admin_url('ozgecmis'), [
            'avatar' => $avatar !== '' ? url($avatar) : null,
        ]);
    }

    /** Ekle / güncelle (id varsa günceller) */
    public function save(): void
    {
        $r = $this->request;
        $id = (int) $r->post('id', 0);
        $type = (string) $r->post('type', '');

        if (!ResumeItem::validType($type)) {
            $this->backAdmin('ozgecmis', 'Geçersiz kayıt türü.');
        }
        $title = mb_substr((string) $r->post('title', ''), 0, 150);
        if ($title === '') {
            $this->backAdmin('ozgecmis', 'Başlık alanı zorunludur.', ['title' => 'Bu alan zorunludur.']);
        }

        $month = static fn ($v) => preg_match('/^\d{4}-\d{2}$/', (string) $v) ? (string) $v : null;
        $url = (string) $r->post('url', '');
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $this->backAdmin('ozgecmis', 'Bağlantı http(s):// ile başlamalıdır.', ['url' => 'Geçerli bir bağlantı girin.']);
        }

        $data = [
            'type'         => $type,
            'title'        => $title,
            'organization' => mb_substr((string) $r->post('organization', ''), 0, 150) ?: null,
            'location'     => mb_substr((string) $r->post('location', ''), 0, 100) ?: null,
            'start_date'   => $month($r->post('start_date')),
            'end_date'     => $r->post('is_current') ? null : $month($r->post('end_date')),
            'is_current'   => $r->post('is_current') ? 1 : 0,
            'description'  => mb_substr((string) $r->post('description', ''), 0, 3000) ?: null,
            'level'        => in_array($type, ['skill', 'language'], true) ? max(0, min(100, (int) $r->post('level', 50))) : null,
            'url'          => $url ?: null,
        ];
        // Sıra sürükle-bırak ile belirlenir: düzenlemede korunur, yeni kayıt listenin sonuna eklenir
        if (!$id) {
            $data['sort_order'] = (int) ResumeItem::db()->fetchColumn('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM resume_items WHERE type = ?', [$type]);
        }

        if ($data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
            $this->backAdmin('ozgecmis', 'Bitiş tarihi başlangıçtan önce olamaz.', ['end_date' => 'Bitiş tarihi başlangıçtan önce olamaz.']);
        }

        if ($id) {
            if (!ResumeItem::find($id)) {
                $this->backAdmin('ozgecmis', 'Kayıt bulunamadı.');
            }
            ResumeItem::updateById($id, $data);
            $message = '"' . $title . '" güncellendi.';
        } else {
            $id = ResumeItem::create($data);
            $message = '"' . $title . '" eklendi.';
        }

        if (Request::isAjax()) {
            $item = ResumeItem::find($id);
            Response::success($message, [
                'data' => [
                    'id'   => $id,
                    'type' => $type,
                    'html' => $this->view->partial('resume-row', ['item' => $item]),
                ],
            ]);
        }
        $this->respond($message, admin_url('ozgecmis'));
    }

    /** Sürükle-bırak sonrası yeni sıra: ids[] listedeki sırayla kaydedilir */
    public function reorder(): void
    {
        $type = (string) $this->request->post('type', '');
        $ids = array_values(array_unique(array_map('intval', (array) $this->request->post('ids', []))));
        if (!ResumeItem::validType($type) || !$ids) {
            Response::error('Geçersiz sıralama isteği.');
        }
        $db = ResumeItem::db();
        // Yalnızca bu türe ait kayıtlar sıralanır (başka türden kimlik gönderilirse yok sayılır)
        $valid = array_map('intval', array_column($db->fetchAll(
            'SELECT id FROM resume_items WHERE type = ? AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')',
            array_merge([$type], $ids)
        ), 'id'));
        $position = 0;
        foreach ($ids as $itemId) {
            if (in_array($itemId, $valid, true)) {
                $db->query('UPDATE resume_items SET sort_order = ? WHERE id = ?', [$position++, $itemId]);
            }
        }
        Response::success('Sıralama kaydedildi.');
    }

    public function destroy(string $id): void
    {
        $item = ResumeItem::find((int) $id);
        if (!$item) {
            $this->respond('Kayıt bulunamadı.', admin_url('ozgecmis'), [], 'danger');
        }
        ResumeItem::deleteById((int) $item->id);
        $this->respond('"' . $item->title . '" silindi.', admin_url('ozgecmis'));
    }
}
