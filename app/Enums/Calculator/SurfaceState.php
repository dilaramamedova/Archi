<?php

namespace App\Enums\Calculator;

/**
 * What a surface looks like today — the left side of the dependency engine.
 *
 * Three answers are all the technology needs: a bare surface gets the full chain, a
 * prepared one only the finishing steps, an old finish is stripped first and then
 * treated as prepared. The earlier six-way split (shell / plaster ready / paint
 * ready / old finish / old tile / rough floor) is folded in by fromAny().
 */
enum SurfaceState: string
{
    /** Mayak / qara divar, qaba döşəmə. */
    case Bare = 'BARE';

    /** Suvaq və ya stəjka hazırdır — yalnız finiş hazırlığı qalır. */
    case Prepared = 'PREPARED';

    /** Köhnə üzlük var — əvvəl sökülür. */
    case Old = 'OLD';

    private const LEGACY = [
        'SHELL' => self::Bare,
        'ROUGH_FLOOR' => self::Bare,
        'PLASTER_READY' => self::Prepared,
        'PAINT_READY' => self::Prepared,
        'OLD_FINISH' => self::Old,
        'OLD_TILE' => self::Old,
    ];

    /** Accepts the current values and the ones a saved session may still carry. */
    public static function fromAny(mixed $value): ?self
    {
        $value = strtoupper(trim((string) $value));

        return self::tryFrom($value) ?? self::LEGACY[$value] ?? null;
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.states.'.strtolower($this->value);
    }
}
