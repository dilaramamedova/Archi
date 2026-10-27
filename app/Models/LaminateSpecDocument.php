<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Laminate\DocumentKind;
use App\Enums\Laminate\WarrantyScope;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A technical passport, installation guide or conditions document of a laminate.
 *
 * Uploaded files live on the private `local` disk and are only ever served through
 * LaminateDocumentController, which checks who is asking (TZ §5: draft files belong
 * to their owner and the moderator; only an approved card's files are public).
 */
class LaminateSpecDocument extends Model
{
    public const DISK = 'local';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kind' => DocumentKind::class,
            'warranty_scope' => WarrantyScope::class,
            'detached_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(LaminateSpec::class, 'laminate_spec_id');
    }

    public function isLink(): bool
    {
        return $this->external_url !== null;
    }

    /**
     * Who may open this document. Public visitors only ever see the documents of a
     * card that is approved, visible and still attached; the seller always sees their
     * own; an admin sees everything — they moderate it.
     */
    public function isViewableBy(?User $user): bool
    {
        $product = $this->spec?->product;

        if (! $product || $this->detached_at !== null) {
            return $user !== null && $user->role === UserRole::Admin;
        }

        if ($user !== null && ($user->role === UserRole::Admin || $user->id === $product->user_id)) {
            return true;
        }

        return $product->is_approved && $product->is_visible;
    }
}
