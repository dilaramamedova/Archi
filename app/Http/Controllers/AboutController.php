<?php

namespace App\Http\Controllers;

use App\Models\AboutStat;
use App\Models\AboutValue;

class AboutController extends Controller
{
    public function index()
    {
        $stats = AboutStat::active()->ordered()->get();
        $values = AboutValue::active()->ordered()->get();

        return view('pages.about', compact('stats', 'values'));
    }
}
