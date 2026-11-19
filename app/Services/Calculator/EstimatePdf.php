<?php

namespace App\Services\Calculator;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The «PDF yüklə» file of both calculators (TZ №2 19.11.2026, §39).
 *
 * WHAT WAS WRONG. The file was rendered by dompdf inside the request, from scratch,
 * every time it was asked for. Measured on Leyla Loft (247 lines, 10 pages): 2.3 s
 * and ≈ 100 MB of PHP memory on a cold worker (the DejaVu font is parsed first),
 * 1.2 s warm, and a 1.3 MB file because the whole font was embedded. The first
 * request of a fresh worker is the expensive one, a second click or the browser's
 * own retry starts a second full render in parallel, and the page navigated to the
 * URL blindly — so whatever the server or the proxy in front of it answered on that
 * first request (here a 503), the visitor saw nothing, and the immediate repeat,
 * served by a warm worker, worked.
 *
 * WHAT IT DOES NOW.
 *   - A snapshot never changes, so its PDF is rendered ONCE and kept on the local
 *     disk; every later request is a file read.
 *   - The font is subset (73 KB instead of 1.3 MB for the same 10 pages), and the
 *     render gets its own time and memory budget.
 *   - While one request renders, another one for the same file does not start a
 *     second render: it gets 202 + Retry-After and the page polls (every 2 s, up to
 *     15 s) with a loading state on the button. A failed render is reported and
 *     answered 503 + Retry-After, so the page retries, and only then tells the
 *     visitor «PDF hazırlanmadı, yenidən cəhd edin».
 */
class EstimatePdf
{
    /** How long one render may hold the lock, seconds. */
    private const LOCK_SECONDS = 120;

    /** A 10-page estimate peaks at ≈ 100 MB; the render must not die at a 128 MB limit. */
    private const MEMORY_LIMIT = '256M';

    private const DIRECTORY = 'calculator-pdf';

    /**
     * @param  string  $key  a stable id of the document (snapshot uuid + locale…)
     * @param  callable(): array{0: string, 1: array}  $view  [view name, data] — only called when rendering
     */
    public function download(string $key, string $filename, callable $view, bool $keep = true): Response|JsonResponse
    {
        // A document that prints «now» (the quick calculator's one page) is never kept:
        // a cached copy would show the time of the first download, not this one.
        if (! $keep) {
            return $this->renderFresh($key, $filename, $view);
        }

        $disk = Storage::disk('local');
        $path = self::DIRECTORY.'/'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $key).'.pdf';

        if (! $disk->exists($path)) {
            $lock = Cache::lock('calculator-pdf:'.$key, self::LOCK_SECONDS);

            if (! $lock->get()) {
                return $this->notReady(202);
            }

            try {
                // Another request may have finished between the check and the lock.
                if (! $disk->exists($path)) {
                    $this->raiseLimits();

                    [$name, $data] = $view();

                    $disk->put($path, $this->render($name, $data));
                }
            } catch (Throwable $e) {
                report($e);

                return $this->notReady(503);
            } finally {
                $lock->release();
            }
        }

        return $this->file($disk->get($path), $filename);
    }

    /** Rendered for this request only — still one render at a time per document. */
    private function renderFresh(string $key, string $filename, callable $view): Response|JsonResponse
    {
        $lock = Cache::lock('calculator-pdf:'.$key, self::LOCK_SECONDS);

        if (! $lock->get()) {
            return $this->notReady(202);
        }

        try {
            $this->raiseLimits();
            [$name, $data] = $view();

            return $this->file($this->render($name, $data), $filename);
        } catch (Throwable $e) {
            report($e);

            return $this->notReady(503);
        } finally {
            $lock->release();
        }
    }

    private function render(string $name, array $data): string
    {
        return Pdf::loadView($name, $data)
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    private function file(string $bytes, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function raiseLimits(): void
    {
        // Only ever raise the limit: an unlimited process (CLI, a queue worker, the test
        // suite) must stay unlimited — setting 120 there killed the next long test.
        $max = (int) ini_get('max_execution_time');
        if (function_exists('set_time_limit') && $max > 0 && $max < self::LOCK_SECONDS) {
            @set_time_limit(self::LOCK_SECONDS);
        }

        $current = ini_get('memory_limit');

        if ($current !== false && $current !== '-1' && $this->bytes($current) < $this->bytes(self::MEMORY_LIMIT)) {
            @ini_set('memory_limit', self::MEMORY_LIMIT);
        }
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }

    /** 202 = being rendered by another request; 503 = the render failed — both: ask again. */
    private function notReady(int $status): JsonResponse
    {
        return response()
            ->json(['status' => $status === 202 ? 'PENDING' : 'FAILED', 'retryAfter' => 2], $status)
            ->header('Retry-After', '2');
    }
}
