<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LaminateSpecDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves one laminate document (TZ «Ламинат» §5 «Файлы и ссылки»).
 *
 * A document nobody may see answers 404, not 403: the existence of a seller's
 * draft passport is itself not public information. External links are a redirect —
 * the server never fetches them.
 */
final class LaminateDocumentController extends Controller
{
    public function __invoke(Request $request, LaminateSpecDocument $document): RedirectResponse|StreamedResponse
    {
        $document->loadMissing('spec.product');

        abort_unless($document->isViewableBy($request->user()), 404);

        if ($document->isLink()) {
            return redirect()->away($document->external_url);
        }

        $disk = Storage::disk(LaminateSpecDocument::DISK);
        abort_unless($document->storage_key && $disk->exists($document->storage_key), 404);

        $name = $document->original_name ?: basename($document->storage_key);

        // PDFs open in the browser; Word files download. The stored MIME is the one
        // finfo read from the content at upload, not anything the client claimed.
        $inline = $document->mime_type === 'application/pdf';

        return $disk->response($document->storage_key, $name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ], $inline ? 'inline' : 'attachment');
    }
}
