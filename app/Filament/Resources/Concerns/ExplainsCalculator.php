<?php

namespace App\Filament\Resources\Concerns;

use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

/**
 * The plain-language layer over the calculator's admin screens.
 *
 * These tables are the client's own price list, and the person editing them is a
 * manager, not the developer who named the columns. Left alone, the screens show
 * `WALL_PUTTY`, `quantity_source = NetWallAreaSelected` and a bare number — true,
 * unreadable, and therefore never edited with confidence.
 *
 * Every screen that uses this concern opens with the same three answers:
 *   1. what this table is, in one sentence a non-technical reader can act on,
 *   2. where on the site the numbers show up,
 *   3. what happens the moment Save is pressed.
 *
 * The wording lives here rather than in each resource so the seven screens explain
 * themselves in one consistent voice.
 */
trait ExplainsCalculator
{
    /**
     * The intro panel every calculator screen starts with.
     *
     * @param  string  $what   one sentence: what this table holds
     * @param  string  $where  where the visitor sees it on the site
     * @param  array<int, string>  $rules  the handful of things that are easy to get wrong
     */
    /**
     * Each screen answers these three questions once; the form and the list page both
     * render the same answers, so they can never drift apart.
     *
     * @return array{what: string, where: string, rules: array<int, string>}
     */
    abstract public static function explanationParts(): array;

    /** The panel at the top of the create/edit form. */
    protected static function explainer(): Section
    {
        ['what' => $what, 'where' => $where, 'rules' => $rules] = static::explanationParts();

        return Section::make()
            ->schema([
                Callout::make()
                    ->heading('Bu bölmə nəyi idarə edir?')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->schema([
                        Html::make(self::explanationHtml($what, $where, $rules)),
                    ]),
            ]);
    }

    /**
     * The same explanation for the list page, where the admin actually lands first.
     *
     * The form-level Callout is only seen once a row is already open — by then the
     * reader has had to decide which row to open. The list needs the answers before
     * that decision, not after it.
     */
    public static function listSubheading(): HtmlString
    {
        ['what' => $what, 'where' => $where, 'rules' => $rules] = static::explanationParts();

        return new HtmlString(
            '<div style="max-width:78ch;border-inline-start:3px solid rgba(120,120,120,.35);'
            .'padding-inline-start:1rem">'
            .self::explanationHtml($what, $where, $rules)->toHtml()
            .'</div>'
        );
    }

    /** @param array<int, string> $rules */
    private static function explanationHtml(string $what, string $where, array $rules): HtmlString
    {
        $items = implode('', array_map(
            fn (string $rule) => '<li style="margin-block:.2rem">'.$rule.'</li>',
            $rules,
        ));

        return new HtmlString(
            '<div style="font-size:.8125rem;line-height:1.6">'
            .'<p style="margin-block:0 .4rem"><strong>Bu səhifə nədir?</strong> '.$what.'</p>'
            .'<p style="margin-block:0 .4rem"><strong>Saytda harada görünür?</strong> '.$where.'</p>'
            .'<p style="margin-block:0 .4rem"><strong>Dəyişiklik nə vaxt işə düşür?</strong> '
            .'Yadda saxlayan kimi. Növbəti hesablama artıq yeni rəqəmlə aparılır — '
            .'proqramçıya ehtiyac yoxdur. Müştərinin əvvəllər saxladığı smetalar isə dəyişmir.</p>'
            .($items !== ''
                ? '<p style="margin-block:.5rem .2rem"><strong>Diqqət edin:</strong></p>'
                    .'<ul style="margin:0;padding-inline-start:1.1rem;list-style:disc">'.$items.'</ul>'
                : '')
            .'</div>'
        );
    }

    /** The sentence repeated wherever a price may legitimately be left empty. */
    protected static function emptyPriceHint(): string
    {
        return 'Boş buraxsanız qiymət uydurulmur: smetada sətir qalır, məbləğin yerinə '
            .'«qiymət dəqiqləşdirilir» yazılır. Pulsuz göstərmək üçün 0 yazmayın — '
            .'0 «bu iş pulsuzdur» deməkdir.';
    }
}
