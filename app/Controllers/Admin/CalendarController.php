<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Support\ActivityLog;

/**
 * Takvim: paneldeki işlemlerin günlüğü (Equation / FullCalendar).
 * Kayıtlar otomatik oluşur (App\Support\ActivityLog); bu sayfa yalnızca gösterir.
 */
class CalendarController extends AdminController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $counts = array_column($db->fetchAll('SELECT category, COUNT(*) AS c FROM activity_log GROUP BY category'), 'c', 'category');

        $this->render('calendar/index', [
            'pageTitle'   => 'Takvim',
            'breadcrumbs' => ['Takvim' => null],
            'categories'  => ActivityLog::CATEGORIES,
            'counts'      => $counts,
            'today'       => (int) $db->fetchColumn('SELECT COUNT(*) FROM activity_log WHERE created_at >= CURDATE()'),
            'week'        => (int) $db->fetchColumn('SELECT COUNT(*) FROM activity_log WHERE created_at >= CURDATE() - INTERVAL 6 DAY'),
            'total'       => (int) array_sum($counts),
        ]);
    }

    /** FullCalendar olay kaynağı: ?start=...&end=...&kategori=proje,eposta */
    public function events(): void
    {
        $start = $this->date((string) $this->request->get('start', ''), '-1 month');
        $end = $this->date((string) $this->request->get('end', ''), '+1 month');
        $categories = array_filter(explode(',', (string) $this->request->get('kategori', '')));

        $events = [];
        foreach (ActivityLog::between($start, $end, $categories) as $row) {
            [$label, $color, $icon] = ActivityLog::CATEGORIES[$row['category']] ?? ActivityLog::CATEGORIES['ayar'];
            $events[] = [
                'id'            => (int) $row['id'],
                'title'         => $row['title'],
                'start'         => str_replace(' ', 'T', $row['created_at']),
                'allDay'        => false,
                'classNames'    => ['event-fc-color', 'fc-bg-' . $color],
                'extendedProps' => [
                    'category' => $row['category'],
                    'label'    => $label,
                    'color'    => $color,
                    'detail'   => $row['detail'],
                    'url'      => $row['url'] !== null ? admin_url($row['url']) : null,
                    'time'     => substr($row['created_at'], 11, 5),
                    'date'     => format_date($row['created_at'], 'd.m.Y'),
                ],
            ];
        }
        // Response::json başarı günlüğüne yazmasın diye "message" alanı yok
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** FullCalendar ISO tarihini (2026-11-01T00:00:00+03:00) güvenli SQL tarihine çevirir */
    private function date(string $value, string $fallback): string
    {
        $ts = $value !== '' ? strtotime(substr($value, 0, 19)) : false;
        return date('Y-m-d H:i:s', $ts ?: strtotime($fallback));
    }
}
