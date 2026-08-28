<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The detailed calculator shows classifier labels — «Səthin hazırkı vəziyyəti»,
 * «Mayak / qara divar», «Laminat», «Gərilmə tavan» — that are NOT the names of the
 * work rates their prices live in. An admin who searches the label they saw on the
 * site found nothing and concluded the price is not manageable.
 *
 * This appends each site label to the search_keywords of every work rate that label
 * actually triggers (the mapping mirrors DependencyEngine), so both the global
 * search and the İş qiymətləri table find them. Only search_keywords is touched:
 * rates, names and notes an admin may have edited stay exactly as they are.
 */
return new class extends Migration
{
    /** work_id => site labels shown by the calculator that lead to this work. */
    private const LABELS = [
        // «Səthin hazırkı vəziyyəti» options and the chains they switch on
        'WALL_PRIMER_BASE' => ['Mayak / qara divar', 'Səthin hazırkı vəziyyəti'],
        'WALL_PLASTER' => ['Mayak / qara divar', 'Səthin hazırkı vəziyyəti'],
        'CEIL_PLASTER' => ['Mayak / qara divar', 'Səthin hazırkı vəziyyəti'],
        'CEIL_PRIMER_BASE' => ['Mayak / qara divar', 'Səthin hazırkı vəziyyəti'],
        'WALL_PUTTY' => ['Suvaq hazırdır', 'Mayak / qara divar'],
        'WALL_SANDING' => ['Suvaq hazırdır'],
        'CEIL_PUTTY' => ['Suvaq hazırdır', 'Mayak / qara divar'],
        'CEIL_SANDING' => ['Suvaq hazırdır'],
        'WALL_PRIMER_FINISH' => ['Boyaya hazırdır'],
        'DEM_WALL_FINISH' => ['Köhnə üzlük var'],
        'DEM_WALL_TILE' => ['Köhnə kafel var'],
        'FLOOR_SCREED' => ['Qaba döşəmə'],

        // «Döşəmə örtüyü» finish options
        'FLOOR_TILE_INSTALL' => ['Döşəmə örtüyü', 'Döşəmə kafeli'],
        'FLOOR_LAMINATE_INSTALL' => ['Döşəmə örtüyü', 'Laminat'],
        'FLOOR_SPC_INSTALL' => ['Döşəmə örtüyü', 'SPC'],
        'FLOOR_PARQUET_INSTALL' => ['Döşəmə örtüyü', 'Parket'],
        'FLOOR_STONE_INSTALL' => ['Döşəmə örtüyü', 'Təbii daş'],

        // «Divar üzlüyü» finish options
        'WALL_PAINT' => ['Divar üzlüyü', 'Boya'],
        'WALL_WALLPAPER' => ['Divar üzlüyü', 'Divar kağızı'],
        'WALL_DECOR_PLASTER' => ['Divar üzlüyü', 'Dekorativ suvaq'],
        'TILE_INSTALL_STANDARD' => ['Divar üzlüyü', 'Divar kafeli'],
        'WALL_PANEL_INSTALL' => ['Divar üzlüyü', 'Divar paneli'],
        'WALL_STONE_INSTALL' => ['Divar üzlüyü', 'Divar daşı'],

        // «Tavan üzlüyü» finish options
        'CEIL_PAINT' => ['Tavan üzlüyü', 'Tavan boyası'],
        'CEIL_GYPSUM_FLAT' => ['Tavan üzlüyü', 'Birsəviyyəli alçıpan'],
        'CEIL_GYPSUM_COMPLEX' => ['Tavan üzlüyü', 'Çoxsəviyyəli alçıpan'],
        'CEIL_STRETCH' => ['Tavan üzlüyü', 'Gərilmə tavan'],

        // «İsti döşəmə» toggle in the room step
        'FLOOR_HEAT_ELECTRIC' => ['İsti döşəmə'],
        'FLOOR_HEAT_WATER' => ['İsti döşəmə'],
    ];

    public function up(): void
    {
        foreach (self::LABELS as $workId => $labels) {
            $current = DB::table('calculator_work_rates')->where('work_id', $workId)->value('search_keywords');
            $terms = array_values(array_filter(array_map('trim', explode(';', (string) $current))));

            foreach ($labels as $label) {
                // Case-sensitive on purpose: «SPC» and «spc» are the same word to
                // the search anyway, this only prevents duplicate rows here.
                if (! in_array(mb_strtolower($label), array_map('mb_strtolower', $terms), true)) {
                    $terms[] = $label;
                }
            }

            DB::table('calculator_work_rates')
                ->where('work_id', $workId)
                ->update(['search_keywords' => implode('; ', $terms)]);
        }
    }

    public function down(): void
    {
        foreach (self::LABELS as $workId => $labels) {
            $current = DB::table('calculator_work_rates')->where('work_id', $workId)->value('search_keywords');
            $lower = array_map('mb_strtolower', $labels);
            $terms = array_values(array_filter(
                array_map('trim', explode(';', (string) $current)),
                fn (string $term) => $term !== '' && ! in_array(mb_strtolower($term), $lower, true),
            ));

            DB::table('calculator_work_rates')
                ->where('work_id', $workId)
                ->update(['search_keywords' => $terms === [] ? null : implode('; ', $terms)]);
        }
    }
};
