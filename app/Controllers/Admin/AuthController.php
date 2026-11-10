<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\RememberMe;
use App\Core\Session;

class AuthController extends AdminController
{
    public function loginForm(): void
    {
        $this->render('auth/login', ['pageTitle' => 'Giriş Yap']);
    }

    public function login(): void
    {
        if (Auth::isLocked()) {
            $this->backAdmin('giris', 'Çok fazla hatalı deneme. ' . ceil(Auth::lockRemaining() / 60) . ' dakika sonra tekrar deneyin.');
        }

        $login = (string) $this->request->post('login', '');
        $password = (string) $this->request->post('password', '');

        if ($login === '' || $password === '') {
            $this->backAdmin('giris', 'Kullanıcı adı ve şifre zorunludur.');
        }

        if (!Auth::attempt($login, $password)) {
            $this->backAdmin('giris', 'Kullanıcı adı veya şifre hatalı.');
        }

        // "Beni hatırla": 30 gün boyunca bu tarayıcıda şifre sorulmaz
        if ($this->request->post('remember')) {
            RememberMe::issue((int) Auth::id());
        }

        Session::flash('success', 'Hoş geldiniz, ' . Auth::user()->name . '!');
        $this->redirectAdmin();
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start();
        Session::flash('info', 'Güvenli bir şekilde çıkış yaptınız.');
        $this->redirectAdmin('giris');
    }
}
