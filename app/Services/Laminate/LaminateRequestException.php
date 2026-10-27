<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A rejected laminate request, with a machine code per field (TZ §7):
 *
 *   {"message": "...", "errors": {"laminate.pack_area_m2": ["..."]},
 *    "codes": {"laminate.pack_area_m2": "POSITIVE_REQUIRED"}}
 *
 * `errors` keeps Laravel's validation shape on purpose, so the product form shows
 * these messages under the right field exactly as it shows every other error.
 * `codes` is what an API client — and the tests — match on.
 */
final class LaminateRequestException extends RuntimeException
{
    /**
     * @param  array<string, string>  $messages  field path => message
     * @param  array<string, string>  $codes  field path => code
     * @param  array<string, mixed>  $extra  additional top-level keys (e.g. missing_fields)
     */
    public function __construct(
        private readonly array $messages,
        private readonly array $codes,
        private readonly int $status = 422,
        private readonly array $extra = [],
    ) {
        parent::__construct((string) (reset($messages) ?: 'Invalid laminate data'));
    }

    /** @return array<string, string> */
    public function codes(): array
    {
        return $this->codes;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * A rejected payload is a validation outcome, not a fault — it must not land in
     * laravel.log as an ERROR with a stack trace on every mistyped thickness.
     *
     * Laravel's handler skips its own logging when report() returns anything other
     * than `false` (Handler::reportThrowable). Returning true means "handled, do not
     * log"; returning false would log it anyway.
     */
    public function report(): bool
    {
        return true;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => array_map(fn (string $m) => [$m], $this->messages),
            'codes' => $this->codes,
            ...$this->extra,
        ], $this->status);
    }
}
