<?php

namespace App\Controllers\Admin;

use App\Support\Stats;

/**
 * İstatistikler: günlük tekil ziyaretçi, en çok incelenen projeler ve sayfalar.
 * Sayım App\Support\Stats ile ziyaretçi tarafında otomatik yapılır.
 */
class StatsController extends AdminController
{
    private const RANGES = [7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 90 gün', 365 => 'Son 1 yıl'];

    public function index(): void
    {
        $days = (int) $this->request->get('gun', 30);
        if (!isset(self::RANGES[$days])) {
            $days = 30;
        }
        $this->render('stats/index', [
            'pageTitle'   => 'İstatistikler',
            'breadcrumbs' => ['İstatistikler' => null],
            'days'        => $days,
            'ranges'      => self::RANGES,
            'totals'      => Stats::totals($days),
            'daily'       => Stats::daily($days),
            'projects'    => Stats::topProjects($days, 25),
            'pages'       => Stats::topPages($days, 25),
        ]);
    }
}
