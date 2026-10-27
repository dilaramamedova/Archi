<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DictionaryRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A seller's proposal for a value that is not in a shared dictionary yet: an
 * unlisted brand, or a "Digər" usage class / AC class / joint type for laminate.
 *
 * Until a moderator resolves it the value exists only here — never in brands or
 * attribute_options — so an unconfirmed name cannot leak into filters, into the
 * brand list other sellers pick from, or onto a public card (TZ §1, acceptance 3).
 */
class DictionaryRequest extends Model
{
    public const ENTITY_BRAND = 'brand';

    public const ENTITY_LAMINATE_OPTION = 'laminate_option';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DictionaryRequestStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DictionaryRequestStatus::Pending);
    }

    /**
     * The comparison form of a dictionary name (TZ §6): outer and repeated spaces
     * removed, Unicode and case folded. Used to spot an existing match, never to
     * merge two names silently.
     */
    public static function normalize(string $label): string
    {
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? trim($label);

        if (class_exists(\Normalizer::class)) {
            $label = \Normalizer::normalize($label, \Normalizer::FORM_KC) ?: $label;
        }

        return mb_strtolower($label);
    }
}
