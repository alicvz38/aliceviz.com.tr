<?php

namespace App\Controllers\Admin;

use App\Models\Message;
use App\Models\QuoteRequest;

/** Teklif talepleri: listeleme, durum takibi, not, silme */
class QuoteController extends AdminController
{
    public function index(): void
    {
        $status = (string) $this->request->get('durum', '');
        $where = isset(QuoteRequest::STATUSES[$status]) ? 'status = ?' : '1';
        $params = $where === '1' ? [] : [$status];

        $counts = [];
        foreach (QuoteRequest::STATUSES as $key => $_) {
            $counts[$key] = QuoteRequest::count('status = ?', [$key]);
        }

        $this->render('quotes/index', [
            'pageTitle'   => 'Teklif Talepleri',
            'breadcrumbs' => ['Teklif Talepleri' => null],
            'quotes'      => QuoteRequest::where($where, $params, 'created_at DESC, id DESC', 200),
            'status'      => $status,
            'counts'      => $counts,
            'total'       => array_sum($counts),
        ]);
    }

    public function show(string $id): void
    {
        $quote = QuoteRequest::find((int) $id);
        if (!$quote) {
            $this->respond('Teklif talebi bulunamadı.', admin_url('teklifler'), [], 'danger');
        }
        // İlk açılışta "Yeni" -> "İncelendi"
        if ($quote->status === 'new') {
            QuoteRequest::updateById((int) $quote->id, ['status' => 'seen']);
            $quote = QuoteRequest::find((int) $quote->id);
        }

        $this->render('quotes/show', [
            'pageTitle'   => 'Teklif #' . $quote->id,
            'breadcrumbs' => ['Teklif Talepleri' => admin_url('teklifler'), $quote->name => null],
            'quote'       => $quote,
            'mails'       => Message::where('email = ?', [$quote->email], 'created_at DESC', 20),
        ]);
    }

    public function update(string $id): void
    {
        $quote = QuoteRequest::find((int) $id);
        if (!$quote) {
            $this->respond('Teklif talebi bulunamadı.', admin_url('teklifler'), [], 'danger');
        }
        $status = (string) $this->request->post('status', $quote->status);
        if (!isset(QuoteRequest::STATUSES[$status])) {
            $status = $quote->status;
        }
        QuoteRequest::updateById((int) $quote->id, [
            'status'     => $status,
            'admin_note' => mb_substr((string) $this->request->post('admin_note', ''), 0, 5000) ?: null,
        ]);
        $fresh = QuoteRequest::find((int) $quote->id);
        $this->respond('Teklif güncellendi.', admin_url('teklifler/' . $quote->id), [
            'status' => $status,
            'label'  => $fresh->statusLabel(),
            'color'  => $fresh->statusColor(),
        ]);
    }

    public function destroy(string $id): void
    {
        $quote = QuoteRequest::find((int) $id);
        if ($quote) {
            QuoteRequest::deleteById((int) $quote->id);
        }
        $this->respond('Teklif talebi silindi.', admin_url('teklifler'));
    }
}
