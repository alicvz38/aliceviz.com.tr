<?php

namespace App\Controllers\Site;

use App\Core\Response;
use App\Models\QuoteRequest;
use App\Services\Notifier;
use App\Support\PhoneVerify;
use App\Support\ServiceCatalog;

/** Teklif Al formu (web sitesi / masaüstü uygulama) */
class QuoteController extends SiteController
{
    public function index(): void
    {
        $service = (string) $this->request->get('hizmet', 'web');
        $this->render('quote', [
            'pageTitle'       => 'Ücretsiz Teklif Al',
            'metaDescription' => 'Web sitesi veya masaüstü uygulama projeniz için ücretsiz teklif alın.',
            'selected'        => ServiceCatalog::find($service) ? $service : 'web',
            'selectedPackage' => (string) $this->request->get('paket', ''),
            'budgets'         => ServiceCatalog::budgets(),
            'deadlines'       => ServiceCatalog::deadlines(),
        ]);
    }

    public function store(): void
    {
        $r = $this->request;

        if ((string) $r->post('website', '') !== '') {
            Response::success('Talebiniz alındı. Teşekkürler!');
        }

        $data = [
            'service'  => (string) $r->post('service', ''),
            'package'  => mb_substr((string) $r->post('package', ''), 0, 50) ?: null,
            'name'     => mb_substr((string) $r->post('name', ''), 0, 100),
            'email'    => mb_substr((string) $r->post('email', ''), 0, 150),
            'phone'    => phone_normalize((string) $r->post('phone', '')),
            'company'  => mb_substr((string) $r->post('company', ''), 0, 150) ?: null,
            'budget'   => (string) $r->post('budget', ''),
            'deadline' => (string) $r->post('deadline', ''),
            'message'  => mb_substr((string) $r->post('message', ''), 0, 5000),
        ];

        $errors = [];
        $service = ServiceCatalog::find($data['service']);
        if ($data['service'] === 'other' && empty(ServiceCatalog::form()['other'])) {
            $service = null;
        }
        if (!$service) $errors['service'] = 'Bir hizmet seçin.';
        if ($service && $data['package'] && !isset($service['packages'][$data['package']])) $data['package'] = null;
        if ($data['phone'] === null) $errors['phone'] = 'Telefon numaranızı yazın; size WhatsApp üzerinden de dönüş yapacağım.';
        if ($data['phone'] === false) $errors['phone'] = 'Telefon numarasını 0 (5XX) XXX XX XX biçiminde eksiksiz yazın.';
        elseif (is_string($data['phone']) && !str_starts_with($data['phone'], '0 (5')) $errors['phone'] = 'WhatsApp ile dönüş yapabilmem için cep telefonu numaranızı yazın (05XX ile başlayan).';
        $data['phone'] = $data['phone'] ?: null;
        if (mb_strlen($data['name']) < 2) $errors['name'] = 'Adınızı yazın.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Geçerli bir e-posta adresi yazın.';
        if (!in_array($data['budget'], ServiceCatalog::budgets(), true)) $data['budget'] = null;
        if (!in_array($data['deadline'], ServiceCatalog::deadlines(), true)) $data['deadline'] = null;
        if (mb_strlen($data['message']) < 20) $errors['message'] = 'Projenizi en az birkaç cümleyle anlatın (en az 20 karakter).';
        if (!$r->post('consent')) $errors['consent'] = 'Devam etmek için onay vermelisiniz.';
        if ($errors) {
            $this->back('teklif-al', 'Lütfen işaretli alanları kontrol edin.', $errors);
        }

        if ($wait = $this->throttle('quote', 60)) {
            Response::error("Lütfen {$wait} saniye sonra tekrar deneyin.", [], 429);
        }
        if (QuoteRequest::recentFromIp($r->ip()) >= 5) {
            Response::error('Kısa sürede çok fazla talep gönderildi. Lütfen daha sonra tekrar deneyin.', [], 429);
        }

        $code = PhoneVerify::code();
        $id = QuoteRequest::create($data + ['status' => 'new', 'ip' => $r->ip(), 'phone_code' => $code]);
        $this->hit('quote');

        $quote = QuoteRequest::find($id);
        $summary = 'Hizmet: ' . $quote->serviceLabel() . "\nPaket: " . $quote->packageLabel()
            . "\nBütçe: " . ($data['budget'] ?? '-') . "\nSüre: " . ($data['deadline'] ?? '-')
            . "\nTelefon: " . $data['phone'] . ' (WhatsApp onay kodu: ' . $code . ')' ."\nFirma: " . ($data['company'] ?? '-')
            . "\n\n" . $data['message'];
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        Notifier::newMessage(
            $data['name'],
            $data['email'],
            'Teklif talebi: ' . $quote->serviceLabel(),
            $summary,
            $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . admin_url('teklifler/' . $id)
        );

        $this->respond('Teklif talebiniz alındı! İnceleyip en kısa sürede WhatsApp (' . $data['phone'] . ') ve e-posta (' . $data['email'] . ') üzerinden dönüş yapacağım.', url('teklif-al'), [
            'id'          => $id,
            'verify_url'  => PhoneVerify::enabled() ? PhoneVerify::link($code, 'teklif talebimi', $id) : '',
            'verify_code' => $code,
        ]);
    }
}
