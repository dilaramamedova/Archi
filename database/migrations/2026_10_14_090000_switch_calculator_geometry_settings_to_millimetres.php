<?php

use App\Support\VersionedCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The calculator now collects every room and opening dimension in millimetres, so
 * the five geometry defaults the admin panel exposes move with it: a manager editing
 * "default door width" must not be looking at 0.9 while the visitor's field says 900.
 *
 * Only the unit changes. 0.9 m and 900 mm are the same door — no price, tariff,
 * coefficient or norm is touched here.
 */
return new class extends Migration
{
    /** key => [new key, multiplier] */
    private const RENAMES = [
        'DEFAULT_ROOM_HEIGHT' => 'DEFAULT_ROOM_HEIGHT_MM',
        'DEFAULT_DOOR_WIDTH' => 'DEFAULT_DOOR_WIDTH_MM',
        'DEFAULT_DOOR_HEIGHT' => 'DEFAULT_DOOR_HEIGHT_MM',
        'DEFAULT_WINDOW_WIDTH' => 'DEFAULT_WINDOW_WIDTH_MM',
        'DEFAULT_WINDOW_HEIGHT' => 'DEFAULT_WINDOW_HEIGHT_MM',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->convert($old, $new, fn (float $v) => $v * 1000, 'mm');
        }

        VersionedCache::bump(VersionedCache::CALCULATOR);
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            $this->convert($new, $old, fn (float $v) => $v / 1000, 'm');
        }

        VersionedCache::bump(VersionedCache::CALCULATOR);
    }

    private function convert(string $from, string $to, callable $scale, string $unit): void
    {
        $row = DB::table('calculator_settings')->where('key', $from)->first();

        if (! $row) {
            return;
        }

        // The admin may have edited the value; whatever is there is converted, not
        // replaced with a seed default.
        DB::table('calculator_settings')->where('key', $to)->delete();

        DB::table('calculator_settings')->where('key', $from)->update([
            'key' => $to,
            'value' => (string) round($scale((float) $row->value), 4),
            'unit' => $unit,
            'updated_at' => now(),
        ]);
    }
};
