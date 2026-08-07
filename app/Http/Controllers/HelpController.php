<?php

namespace App\Http\Controllers;

use App\Models\FaqTopic;

class HelpController extends Controller
{
    public function index()
    {
        app()->setLocale(session('locale', config('app.locale')));

        $topics = FaqTopic::active()
            ->ordered()
            ->with(['questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->get();

        return view('pages.help', compact('topics'));
    }
}
