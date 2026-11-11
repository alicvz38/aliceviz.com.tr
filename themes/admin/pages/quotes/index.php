<?php
/**
 * @var App\Core\View $this
 * @var App\Models\QuoteRequest[] $quotes
 * @var string $status
 * @var array $counts
 * @var int $total
 */
$this->layout('main');
$statuses = App\Models\QuoteRequest::STATUSES;
?>
<div class="row">
    <div class="col-12 layout-spacing">
        <div class="widget widget-content-area">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="section-title mb-0"><?= icon('inbox') ?> Teklif Talepleri</div>
                <div class="d-flex gap-2">
                    <a href="<?= e(url('teklif-al')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('eye', 14) ?> Teklif formunu görüntüle</a>
                    <a href="<?= e(admin_url('teklif-formu')) ?>" class="btn btn-outline-primary btn-sm"><?= icon('edit', 14) ?> Formu ve hizmetleri düzenle</a>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="<?= e(admin_url('teklifler')) ?>" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?>">Tümü <span class="badge bg-dark ms-1"><?= (int) $total ?></span></a>
                <?php foreach ($statuses as $key => [$label, $color]): ?>
                    <a href="<?= e(admin_url('teklifler?durum=' . $key)) ?>" class="btn btn-sm <?= $status === $key ? 'btn-' . $color : 'btn-outline-' . $color ?>">
                        <?= e($label) ?> <span class="badge bg-dark ms-1"><?= (int) $counts[$key] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($quotes): ?>
                <div>
                    <table class="table table-hover dt-table-hover" id="quotesTable" style="width:100%" data-datatable data-no-sort="6" data-order="[[0,&quot;desc&quot;]]">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Müşteri</th>
                            <th>Hizmet / Paket</th>
                            <th>Bütçe</th>
                            <th class="text-center">Durum</th>
                            <th>Tarih</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($quotes as $q): ?>
                            <tr class="<?= $q->status === 'new' ? 'fw-bold' : '' ?>">
                                <td class="text-muted"><?= (int) $q->id ?></td>
                                <td>
                                    <a href="<?= e(admin_url('teklifler/' . $q->id)) ?>"><?= e($q->name) ?></a>
                                    <div class="text-muted small"><?= e($q->email) ?><?= $q->company ? ' · ' . e($q->company) : '' ?></div>
                                </td>
                                <td><?= e($q->serviceLabel()) ?><div class="text-muted small"><?= e($q->packageLabel()) ?></div></td>
                                <td class="small"><?= e($q->budget ?: '-') ?></td>
                                <td class="text-center"><span class="badge badge-light-<?= e($q->statusColor()) ?>"><?= e($q->statusLabel()) ?></span></td>
                                <td class="text-muted small" title="<?= e(format_date($q->created_at, 'd.m.Y H:i')) ?>"><?= e(time_ago($q->created_at)) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= e(admin_url('teklifler/' . $q->id)) ?>" class="btn btn-outline-primary" title="Görüntüle"><?= icon('eye') ?></a>
                                        <?php if ($q->phone): ?><a href="<?= e(whatsapp_href($q->phone, 'Merhaba ' . $q->name . ', ' . preg_replace('#^https?://#', '', site_origin()) . ' üzerinden gönderdiğiniz ' . $q->serviceLabel() . ' teklif talebiniz için yazıyorum.')) ?>" target="_blank" rel="noopener" class="btn btn-outline-success" title="WhatsApp ile yanıtla"><?= icon('whatsapp') ?></a><?php endif; ?>
                                        <a href="<?= e(admin_url('mesajlar/yeni?teklif=' . $q->id)) ?>" class="btn btn-outline-info" title="E-posta ile yanıtla"><?= icon('send') ?></a>
                                        <form method="post" action="<?= e(admin_url('teklifler/' . $q->id . '/sil')) ?>" data-ajax data-ajax-success="remove">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-outline-danger" title="Sil" data-confirm="Bu teklif talebi silinsin mi?"><?= icon('trash') ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <p>Bu filtrede teklif talebi yok.</p>
                    <a href="<?= e(url('teklif-al')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><?= icon('external-link', 14) ?> Teklif formunu görüntüle</a>
                    <a href="<?= e(admin_url('teklif-formu')) ?>" class="btn btn-outline-primary btn-sm"><?= icon('edit', 14) ?> Formu düzenle</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
