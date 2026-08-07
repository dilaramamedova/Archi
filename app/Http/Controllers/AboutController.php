<?php

namespace App\Http\Controllers;

use App\Models\AboutStat;
use App\Models\AboutStep;
use App\Models\AboutTeamMember;
use App\Models\Setting;

class AboutController extends Controller
{
    public function index()
    {
        $stats = AboutStat::active()->ordered()->get();
        $steps = AboutStep::active()->ordered()->get();
        $team = AboutTeamMember::active()->ordered()->get();

        $features = [
            [
                'title' => Setting::get('about_feat1_title', t('about.what.f1_title')),
                'desc' => Setting::get('about_feat1_desc', t('about.what.f1_desc')),
                'link' => Setting::get('about_feat1_link', t('about.what.f1_link')),
                'url' => Setting::get('about_feat1_url', '/catalog'),
            ],
            [
                'title' => Setting::get('about_feat2_title', t('about.what.f2_title')),
                'desc' => Setting::get('about_feat2_desc', t('about.what.f2_desc')),
                'link' => Setting::get('about_feat2_link', t('about.what.f2_link')),
                'url' => Setting::get('about_feat2_url', '/specialists'),
            ],
            [
                'title' => Setting::get('about_feat3_title', t('about.what.f3_title')),
                'desc' => Setting::get('about_feat3_desc', t('about.what.f3_desc')),
                'link' => Setting::get('about_feat3_link', t('about.what.f3_link')),
                'url' => Setting::get('about_feat3_url', '/calculator'),
            ],
        ];

        $joinCards = [
            [
                'title' => Setting::get('about_join_card1_title', t('about.join.c1_title')),
                'desc' => Setting::get('about_join_card1_desc', t('about.join.c1_desc')),
                'btn' => Setting::get('about_join_card1_btn', t('about.join.c1_btn')),
                'url' => Setting::get('about_join_card1_url', '/register?role=specialist'),
            ],
            [
                'title' => Setting::get('about_join_card2_title', t('about.join.c2_title')),
                'desc' => Setting::get('about_join_card2_desc', t('about.join.c2_desc')),
                'btn' => Setting::get('about_join_card2_btn', t('about.join.c2_btn')),
                'url' => Setting::get('about_join_card2_url', '/business-register'),
            ],
        ];

        return view('pages.about', compact('stats', 'steps', 'team', 'features', 'joinCards'));
    }
}
