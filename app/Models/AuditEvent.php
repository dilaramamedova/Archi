<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only journal of edits, approvals and rejections (TZ §2 «audit_events»).
 *
 * It is what keeps the history the TZ asks for when a dependent answer is cleared:
 * the card shows the new state, and the previous values stay readable here.
 */
class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [before, after]
     */
    public static function record(?int $actorId, string $entityType, int $entityId, string $action, array $changes = []): self
    {
        return self::create([
            'actor_id' => $actorId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'changes' => $changes === [] ? null : $changes,
        ]);
    }
}
