<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\Laminate\DocumentKind;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WarrantyScope;
use App\Models\LaminateSpec;
use App\Models\LaminateSpecDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Passports, installation guides and conditions documents of a laminate card
 * (TZ «Ламинат» §5 «Файлы и ссылки»).
 *
 * Each entry is either an uploaded file or an absolute HTTPS link — exactly one.
 * The file's type is decided by its content, never by its name, and the file is
 * stored on the private disk where the web server cannot execute or serve it.
 * External links are stored as given and never fetched by the server.
 *
 * Request shape:
 *   laminate_documents[<i>][kind]            technical_passport | installation | warranty_conditions | water_conditions
 *   laminate_documents[<i>][file]            upload, or
 *   laminate_documents[<i>][url]             https://…
 *   laminate_documents[<i>][warranty_scope]  residential | commercial (warranty_conditions only)
 *   laminate_documents_kept[]                ids of stored documents that stay; absent = keep all
 */
final class LaminateDocuments
{
    public const MAX_DOCUMENTS = 10;

    /**
     * The site has no standard document upload, so the TZ's fallback applies:
     * PDF / DOC / DOCX up to 20 MB.
     */
    public const MAX_KB = 20480;

    /** Extension => MIME types finfo reports for real files of that kind. */
    private const MIMES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/cdfv2', 'application/vnd.ms-office'],
        // A .docx is a zip container; older libmagic builds report it as one.
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];

    /**
     * @param  array<string, mixed>  $values  validated card values (parents decide what is allowed)
     * @param  list<array{scope: string}>  $warranties
     * @return array{new: list<array<string, mixed>>, keep: ?list<int>}
     *
     * @throws LaminateRequestException
     */
    public function validate(Request $request, array $values, array $warranties, ?LaminateSpec $current): array
    {
        $messages = [];
        $codes = [];
        $fail = function (string $path, string $code, ?string $parent = null) use (&$messages, &$codes): void {
            if (! isset($messages[$path])) {
                $messages[$path] = self::message($code, $parent);
                $codes[$path] = $code;
            }
        };

        $existing = $current ? $current->documents()->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        $keep = null;
        if ($request->exists('laminate_documents_kept')) {
            $keep = array_values(array_intersect(
                $existing,
                array_map('intval', array_filter((array) $request->input('laminate_documents_kept', []), fn ($v) => ! is_array($v) && $v !== '')),
            ));
        }

        $entries = $request->input('laminate_documents', []);
        $files = $request->file('laminate_documents', []);
        $entries = is_array($entries) ? $entries : [];
        $files = is_array($files) ? $files : [];

        $activeScopes = array_column($warranties, 'scope');
        $new = [];

        foreach (array_unique([...array_keys($entries), ...array_keys($files)]) as $i) {
            $path = "laminate_documents.$i";
            $entry = is_array($entries[$i] ?? null) ? $entries[$i] : [];
            $file = $files[$i]['file'] ?? null;
            $url = is_string($entry['url'] ?? null) ? trim($entry['url']) : '';

            $kind = is_string($entry['kind'] ?? null) ? DocumentKind::tryFrom($entry['kind']) : null;
            if ($kind === null) {
                $fail("$path.kind", 'INVALID_OPTION');

                continue;
            }

            $hasFile = $file instanceof UploadedFile;
            if ($hasFile === ($url !== '')) {
                // Neither, or both: the TZ allows exactly one source per document.
                $fail("$path.file", 'DOCUMENT_SOURCE');

                continue;
            }

            if ($kind === DocumentKind::WaterConditions && ($values['water_protection'] ?? null) !== Tri::Yes->value) {
                $fail("$path.kind", 'DEPENDENCY_CONFLICT', 'water_protection');

                continue;
            }

            $scope = null;
            if ($kind === DocumentKind::WarrantyConditions) {
                $scope = is_string($entry['warranty_scope'] ?? null) ? WarrantyScope::tryFrom($entry['warranty_scope']) : null;
                if (($values['warranty'] ?? null) !== Tri::Yes->value) {
                    $fail("$path.kind", 'DEPENDENCY_CONFLICT', 'warranty');

                    continue;
                }
                if ($scope === null || ! in_array($scope->value, $activeScopes, true)) {
                    $fail("$path.warranty_scope", 'SCOPE_NOT_ACTIVE');

                    continue;
                }
            }

            if ($hasFile) {
                $code = $this->checkFile($file);
                if ($code !== null) {
                    $fail("$path.file", $code);

                    continue;
                }
                $new[] = ['kind' => $kind->value, 'scope' => $scope?->value, 'file' => $file];
            } else {
                if (! $this->isHttpsUrl($url)) {
                    $fail("$path.url", 'INVALID_URL');

                    continue;
                }
                $new[] = ['kind' => $kind->value, 'scope' => $scope?->value, 'url' => $url];
            }
        }

        $total = count($keep ?? $existing) + count($new);
        if ($total > self::MAX_DOCUMENTS) {
            $fail('laminate_documents', 'TOO_MANY_DOCUMENTS');
        }

        if ($messages !== []) {
            throw new LaminateRequestException($messages, $codes);
        }

        return ['new' => $new, 'keep' => $keep];
    }

    /**
     * Apply the plan after the card has been committed — files are stored once the
     * database write is certain, so a rolled-back request leaves nothing on disk.
     */
    public function sync(LaminateSpec $spec, array $plan, ?int $actorId): void
    {
        if ($plan['keep'] !== null) {
            // Detached, not deleted: the file may still be referenced by history.
            $spec->documents()->whereNotIn('id', $plan['keep'] ?: [0])->update(['detached_at' => now()]);
        }

        $order = (int) ($spec->documents()->max('display_order') ?? 0);

        foreach ($plan['new'] as $doc) {
            $attributes = [
                'laminate_spec_id' => $spec->id,
                'kind' => $doc['kind'],
                'warranty_scope' => $doc['scope'],
                'display_order' => ++$order,
                'created_by' => $actorId,
            ];

            if (isset($doc['file'])) {
                /** @var UploadedFile $file */
                $file = $doc['file'];
                $ext = strtolower($file->getClientOriginalExtension());
                $attributes += [
                    'storage_key' => $file->storeAs('laminate-documents/'.$spec->id, Str::uuid()->toString().'.'.$ext, LaminateSpecDocument::DISK),
                    'mime_type' => $file->getMimeType(),
                    'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                    'size_bytes' => $file->getSize(),
                ];
            } else {
                $attributes += [
                    'external_url' => $doc['url'],
                    'original_name' => Str::limit((string) parse_url($doc['url'], PHP_URL_HOST), 250, ''),
                ];
            }

            LaminateSpecDocument::create($attributes);
        }
    }

    private function checkFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return 'INVALID_FILE';
        }

        if ($file->getSize() > self::MAX_KB * 1024) {
            return 'FILE_TOO_LARGE';
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType()); // finfo — the content, not the name

        if (! isset(self::MIMES[$ext]) || ! in_array($mime, self::MIMES[$ext], true)) {
            return 'INVALID_FILE';
        }

        return null;
    }

    private function isHttpsUrl(string $url): bool
    {
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https' && ! empty($parts['host']);
    }

    private static function message(string $code, ?string $parent = null): string
    {
        $key = 'laminate.errors.'.$code;
        $replace = ['field' => t('laminate.field.documents')];

        if ($parent !== null) {
            $replace['parent'] = t('laminate.field.'.$parent);
        }

        $text = t($key, $replace);

        return is_string($text) && $text !== $key ? $text : $code;
    }
}
