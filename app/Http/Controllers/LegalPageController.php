<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\Support\Str;

class LegalPageController extends Controller
{
    public function show(string $slug)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $page = LegalPage::where('slug', $slug)->active()->firstOrFail();

        // Build the "Bu səhifədə" TOC from the content's <h2> headings and give each
        // one an anchor id so the sidebar can deep-link to it (Figma 1349:10320).
        $content = $page->content;
        $toc = [];

        $content = preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/su', function (array $m) use (&$toc) {
            $text = trim(strip_tags($m[2]));
            $id = 'sec-'.(count($toc) + 1).'-'.Str::slug(Str::limit($text, 40, ''));
            $toc[] = ['id' => $id, 'label' => $text];

            return '<h2 id="'.$id.'"'.$m[1].'>'.$m[2].'</h2>';
        }, $content) ?? $content;

        return view('pages.legal', compact('page', 'content', 'toc'));
    }
}
