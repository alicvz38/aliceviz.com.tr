<?php

namespace App\Controllers\Site;

use App\Models\Project;
use App\Models\ResumeItem;

/** Hakkımda — özgeçmiş (CV) sayfası */
class AboutController extends SiteController
{
    public function index(): void
    {
        $items = ResumeItem::grouped();

        // Toplam deneyim yılı (ilk iş başlangıcından bugüne)
        $years = 0;
        $starts = array_filter(array_map(static fn ($i) => $i->start_date, $items['experience']));
        if ($starts) {
            $years = (int) (new \DateTime(min($starts) . '-01'))->diff(new \DateTime())->y;
        }

        $this->render('about', [
            'pageTitle'       => 'Hakkımda — Özgeçmiş',
            'metaDescription' => str_limit((string) setting('about_text', ''), 160),
            'items'           => $items,
            'skillGroups'     => ResumeItem::skillsByGroup(),
            'stats'           => [
                'years'    => $years,
                'projects' => Project::countPublished(),
                'stars'    => Project::totalStars(),
            ],
            'recent'          => Project::published(6),
        ]);
    }
}
