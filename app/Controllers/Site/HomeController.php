<?php

namespace App\Controllers\Site;

use App\Models\Project;

class HomeController extends SiteController
{
    public function index(): void
    {
        $featured = Project::featured(6);
        if (!$featured) {
            $featured = Project::published(6);
        }

        $this->render('home', [
            'pageTitle' => null,
            'featured'  => $featured,
            'topSkills' => array_slice(\App\Models\ResumeItem::ofType('skill'), 0, 8),
            'stats'     => [
                'projects'  => Project::countPublished(),
                'stars'     => Project::totalStars(),
                'languages' => count(Project::languages()),
            ],
        ]);
    }
}
