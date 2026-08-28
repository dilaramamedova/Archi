<?php

namespace App\Filament\Resources\Concerns;

use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

/**
 * Shows an admin where a calculator screen's numbers land on the site.
 *
 * A screenshot answers "which of these values is that?" faster than any paragraph,
 * and it cannot go stale silently: the images are captured from the live pages, so
 * regenerating them is a single command (see docs/calculator.md).
 *
 * Each screen therefore gets one short sentence and one picture — the warnings that
 * actually matter live on the individual fields, where they are read at the moment
 * of editing rather than skimmed at the top of a page.
 */
trait ExplainsCalculator
{
    /**
     * @return array{text: string, shot: ?string}  one sentence, one screenshot file
     */
    abstract public static function explanationParts(): array;

    /** The panel at the top of the create/edit form. */
    protected static function explainer(): Section
    {
        return Section::make()
            ->schema([Html::make(self::explanationHtml(compact: true))]);
    }

    /** The same, above the list — where the admin actually lands first. */
    public static function listSubheading(): HtmlString
    {
        return self::explanationHtml(compact: false);
    }

    /** The one warning repeated wherever a price may legitimately be left empty. */
    protected static function emptyPriceHint(): string
    {
        return 'Boş buraxsanız smetada «qiymət dəqiqləşdirilir» yazılır. '
            .'Pulsuz göstərmək üçün 0 yazmayın — 0 «bu iş pulsuzdur» deməkdir.';
    }

    private static function explanationHtml(bool $compact): HtmlString
    {
        ['text' => $text, 'shot' => $shot] = static::explanationParts();

        $image = '';

        if ($shot) {
            $src = asset("assets/admin/calculator/{$shot}.png");
            $width = $compact ? '360px' : '520px';

            // Opens full size in a new tab: the thumbnail says "here", the full image
            // is for reading the actual labels.
            $image = '<a href="'.$src.'" target="_blank" rel="noopener" '
                .'style="display:inline-block;margin-top:.5rem;max-width:'.$width.'">'
                .'<img src="'.$src.'" alt="" loading="lazy" '
                .'style="width:100%;height:auto;border:1px solid rgba(120,120,120,.3);border-radius:.5rem">'
                .'</a>';
        }

        return new HtmlString(
            '<div style="font-size:.8125rem;line-height:1.55;max-width:62ch">'
            .'<p style="margin:0">'.$text.'</p>'
            .$image
            .'</div>'
        );
    }
}
