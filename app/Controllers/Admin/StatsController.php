<?php

namespace App\Controllers\Admin;

use App\Support\Stats;

/**
 * İstatistikler: günlük tekil ziyaretçi, en çok incelenen projeler ve sayfalar.
 * Sayım App\Support\Stats ile ziyaretçi tarafında otomatik yapılır.
 * Sayfa canlı yenilenir: WebSocket varsa sunucu değişikliği anında bildirir, yoksa 2 sn'de bir sürüm yoklanır.
 */
class StatsController extends AdminController
{
    private const RANGES = [7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 90 gün', 365 => 'Son 1 yıl'];

    public function index(): void
    {
        $days = $this->days();
        $this->render('stats/index', [
            'pageTitle'   => 'İstatistikler',
            'breadcrumbs' => ['İstatistikler' => null],
            'days'        => $days,
            'ranges'      => self::RANGES,
            'version'     => Stats::version(),
        ] + $this->payload($days));
    }

    /** Canlı yenileme: kartlar, grafik ve tablolar */
    public function data(): void
    {
        $days = $this->days();
        $p = $this->payload($days);
        $this->json([
            'ok'      => true,
            'version' => Stats::version(),
            'totals'  => $p['totals'],
            'chart'   => self::chart($p['daily']),
            'tables'  => $this->view->partial('stats-tables', ['projects' => $p['projects'], 'pages' => $p['pages']]),
        ]);
    }

    /** Değişiklik var mı? (tek hafif sorgu, 2 sn'de bir yoklanır) */
    public function version(): void
    {
        $this->json(['ok' => true, 'version' => Stats::version()]);
    }

    /** Grafik verisi: gün etiketleri + seriler */
    public static function chart(array $daily): array
    {
        return [
            'labels'   => array_map(static fn ($d) => date('d.m', strtotime($d)), array_keys($daily)),
            'visitors' => array_column(array_values($daily), 'site'),
            'projects' => array_column(array_values($daily), 'project'),
        ];
    }

    private function payload(int $days): array
    {
        return [
            'totals'   => Stats::totals($days),
            'daily'    => Stats::daily($days),
            'projects' => Stats::topProjects($days, 25),
            'pages'    => Stats::topPages($days, 25),
        ];
    }

    private function days(): int
    {
        $days = (int) $this->request->get('gun', 30);
        return isset(self::RANGES[$days]) ? $days : 30;
    }
}
