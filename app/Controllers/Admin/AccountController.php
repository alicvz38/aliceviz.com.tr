<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\RememberMe;
use App\Core\Session;
use App\Models\User;

class AccountController extends AdminController
{
    public function index(): void
    {
        $this->render('account/index', [
            'pageTitle'   => 'Hesabım',
            'breadcrumbs' => ['Hesabım' => null],
            'user'        => Auth::user(),
            'devices'     => RememberMe::devices((int) Auth::id()),
        ]);
    }

    /** Profil fotoğrafı (Özgeçmiş'teki fotoğrafla aynıdır: tek kaynak "cv_avatar") */
    public function photo(): void
    {
        $file = $this->request->file('photo');
        if (!$file) {
            $this->respond('Bir fotoğraf seçin.', admin_url('hesabim'), [], 'danger');
        }
        $uploader = new \App\Services\Uploader();
        try {
            $new = $uploader->image($file);
        } catch (\RuntimeException $e) {
            $this->respond($e->getMessage(), admin_url('hesabim'), [], 'danger');
        }
        $uploader->delete((string) \App\Models\Setting::get('cv_avatar', ''));
        \App\Models\Setting::set('cv_avatar', $new);
        $this->respond('Profil fotoğrafı güncellendi; panelde, sitede ve özgeçmişte değişti.', admin_url('hesabim'), ['photo' => profile_photo()]);
    }

    public function removePhoto(): void
    {
        (new \App\Services\Uploader())->delete((string) \App\Models\Setting::get('cv_avatar', ''));
        \App\Models\Setting::set('cv_avatar', '');
        $this->respond('Profil fotoğrafı kaldırıldı.', admin_url('hesabim'));
    }

    /** Hatırlanan tek bir cihazı kapat */
    public function forgetDevice(string $id): void
    {
        RememberMe::forgetDevice((int) Auth::id(), (int) $id);
        $this->respond('Cihaz kaldırıldı; o tarayıcıda tekrar şifre sorulacak.', admin_url('hesabim'));
    }

    /** Bu cihaz hariç hatırlanan tüm cihazları kapat */
    public function forgetOthers(): void
    {
        $n = RememberMe::forgetAll((int) Auth::id(), true);
        $this->respond($n ? $n . ' cihazdaki "Beni hatırla" kapatıldı.' : 'Kapatılacak başka cihaz yok.', admin_url('hesabim'), [], $n ? 'success' : 'info');
    }

    public function update(): void
    {
        $user = Auth::user();
        $r = $this->request;
        $name = (string) $r->post('name', '');
        $username = (string) $r->post('username', '');
        $email = (string) $r->post('email', '');

        if ($name === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $this->backAdmin('hesabim', 'Ad zorunludur; kullanıcı adı 3-50 karakter (harf, rakam, _ . -) olmalıdır.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backAdmin('hesabim', 'Geçerli bir e-posta adresi girin.');
        }
        if (User::count('(username = ? OR email = ?) AND id <> ?', [$username, $email, $user->id]) > 0) {
            $this->backAdmin('hesabim', 'Bu kullanıcı adı veya e-posta başka bir hesapta kullanılıyor.');
        }

        $data = ['name' => $name, 'username' => $username, 'email' => $email];

        $new = (string) $r->post('password', '');
        if ($new !== '') {
            if (!password_verify((string) $r->post('current_password', ''), (string) $user->password)) {
                $this->backAdmin('hesabim', 'Mevcut şifreniz hatalı.');
            }
            if (mb_strlen($new) < 8 || $new !== (string) $r->post('password_confirmation', '')) {
                $this->backAdmin('hesabim', 'Yeni şifre en az 8 karakter olmalı ve tekrarı ile eşleşmelidir.');
            }
            $data['password'] = password_hash($new, PASSWORD_DEFAULT);
        }

        User::updateById((int) $user->id, $data);
        if (isset($data['password'])) {
            // Şifre değişti: diğer cihazlardaki "Beni hatırla" girişleri geçersiz olsun
            RememberMe::forgetAll((int) $user->id, true);
            Session::flash('success', 'Hesap bilgileriniz ve şifreniz güncellendi. Diğer cihazlardaki kayıtlı girişler kapatıldı.');
            $this->redirectAdmin('hesabim');
        }
        Session::flash('success', 'Hesap bilgileriniz güncellendi.');
        $this->redirectAdmin('hesabim');
    }
}
