<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource;

use App\Models\Product;
use App\Services\ProductCard\CardProduct;
use App\Services\ProductCard\CardSchema;
use App\Support\IsoCountries;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * The category card (docs/product-cards.md) in the admin product form — the same
 * schema, rules and stored shape as the seller form (CardSchema / CardProduct).
 * Form state: `card.<attribute id>.{option|options|text|value|length|length_max|width}`,
 * plus the product-level fields origin_country, is_unbranded, collection,
 * availability, lead_time_days and the virtual price_basis.
 */
final class CardForm
{
    /** @var array<string, array|null> per request: Livewire re-renders call this per field */
    private static array $schemas = [];

    public static function schema(mixed $subCategoryId): ?array
    {
        $id = (int) $subCategoryId;

        if ($id <= 0) {
            return null;
        }

        $key = spl_object_id(request()).':'.$id;

        if (! array_key_exists($key, self::$schemas)) {
            // Keep the memo to the current request only.
            if (count(self::$schemas) > 50) {
                self::$schemas = [];
            }
            self::$schemas[$key] = CardSchema::for($id);
        }

        return self::$schemas[$key];
    }

    public static function flush(): void
    {
        self::$schemas = [];
    }

    public static function isCard(callable $get): bool
    {
        return self::schema($get('sub_category_id')) !== null;
    }

    /** The price basis of a board class — shown above the price. */
    public static function basisField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('price_basis')
            ->label('Qiymət bazası')
            ->options(['piece' => 'AZN/ədəd', 'lm' => 'AZN/poqon metr', 'm2' => 'AZN/m²'])
            ->helperText('Teras taxtası: qiymət bir bazada yazılır, satış bütöv taxta ilədir.')
            ->visible(fn (callable $get): bool => (self::schema($get('sub_category_id'))['sale']['type'] ?? null) === CardSchema::SALE_BOARD);
    }

    /** Brand-level fields for the «Əsas» tab. */
    public static function commonFields(): array
    {
        return [
            Forms\Components\Toggle::make('is_unbranded')
                ->label('Brendsiz')
                ->helperText('Kateqoriya kartı: brend seçilmədikdə «Brendsiz» qeyd edilir.')
                ->visible(fn (callable $get): bool => self::isCard($get)),

            Forms\Components\Select::make('origin_country')
                ->label('İstehsal ölkəsi')
                ->options(fn (): array => IsoCountries::options('az'))
                ->searchable()
                ->helperText('Məhsulun faktiki istehsal olunduğu ölkə; brendin qeydiyyat ölkəsi deyil.')
                ->visible(fn (callable $get): bool => self::isCard($get)),

            Forms\Components\TextInput::make('collection')
                ->label('Kolleksiya')
                ->maxLength(120)
                ->visible(fn (callable $get): bool => self::isCard($get)),
        ];
    }

    /** Availability and price basis for the «Qiymət» tab. */
    public static function pricingFields(): array
    {
        return [
            Forms\Components\Select::make('availability')
                ->label('Mövcudluq')
                ->options([Product::IN_STOCK => 'Stokda', Product::ON_ORDER => 'Sifarişlə', Product::UNAVAILABLE => 'Mövcud deyil'])
                ->default(Product::IN_STOCK)
                ->live()
                ->visible(fn (callable $get): bool => self::isCard($get)),

            Forms\Components\TextInput::make('lead_time_days')
                ->label('Hazırlanma müddəti, gün')
                ->numeric()->minValue(1)->maxValue(365)
                ->visible(fn (callable $get): bool => self::isCard($get) && $get('availability') === Product::ON_ORDER),

            Forms\Components\Placeholder::make('card_sale_note')
                ->label('Satış vahidi')
                ->content('Kateqoriya kartı: qiymət m²-ə (və ya seçilmiş bazaya) görədir; paket/rulon/taxta qiyməti və paketin sahəsi kartdakı parametrlərdən avtomatik hesablanır. Stok — satılan vahidlərin sayıdır.')
                ->visible(fn (callable $get): bool => self::isCard($get)),
        ];
    }

    /** The card's own parameters for the «Xüsusiyyətlər» tab. */
    public static function section(): Section
    {
        return Section::make('Kateqoriya kartı')
            ->description('Seçilmiş alt kateqoriyanın parametrləri (müştərinin Excel kartı). Satıcı formu ilə eyni qaydalar: * olanlar məcburidir.')
            ->schema(fn (callable $get): array => self::fields(self::schema($get('sub_category_id'))))
            ->columns(2)
            ->visible(fn (callable $get): bool => self::isCard($get));
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<mixed>
     */
    private static function fields(?array $schema): array
    {
        if (! $schema) {
            return [];
        }

        $components = [];

        foreach ($schema['fields'] as $f) {
            $base = "card.{$f['id']}";
            $label = $f['name'].($f['unit'] ? ", {$f['unit']}" : '');
            $shown = fn (callable $get): bool => CardSchema::holds($f['visible_when'], self::answers($schema, $get));
            $required = fn (callable $get): bool => $f['required'] && $shown($get);

            switch ($f['type']) {
                case 'dropdown':
                    $components[] = Forms\Components\Select::make("{$base}.option")
                        ->label($label)
                        ->options(fn (callable $get): array => self::options($f, self::answers($schema, $get)))
                        ->live()
                        ->required($required)
                        ->helperText($f['hint'])
                        ->visible($shown);
                    $components[] = self::promptText($f, $base, $shown, "{$base}.option");
                    break;

                case 'multiselect':
                    $components[] = Forms\Components\CheckboxList::make("{$base}.options")
                        ->label($label)
                        ->options(fn (callable $get): array => self::options($f, self::answers($schema, $get)))
                        ->columns(4)
                        ->live()
                        ->required($required)
                        ->helperText($f['hint'])
                        ->visible($shown)
                        ->columnSpanFull();
                    $components[] = self::promptText($f, $base, $shown, "{$base}.options");
                    break;

                case 'size':
                    $mixed = fn (callable $get): bool => CardSchema::holds($f['rules']['mixed_when'] ?? ['attr' => '__never__'], self::answers($schema, $get));
                    $listed = $f['presets'] !== [];
                    [$lengths, $widths] = CardSchema::sizeParts($f['presets']);
                    $part = fn (string $path, string $partLabel, array $values) => $listed
                        ? Forms\Components\Select::make($path)->label($partLabel)->options(self::choices($values))
                        : Forms\Components\TextInput::make($path)->label($partLabel);

                    // With a list: one «L × W» choice; a mixed-length format picks min,
                    // max and width from the listed values. Without: typed numbers.
                    if ($listed) {
                        $components[] = Forms\Components\Select::make("{$base}.pair")
                            ->label($label)
                            ->options(collect($f['presets'])->mapWithKeys(fn ($p) => [(string) $p => str_replace('×', ' × ', (string) $p)])->all())
                            ->required(fn (callable $get): bool => $required($get) && ! $mixed($get))
                            ->helperText($f['hint'])
                            ->visible(fn (callable $get): bool => $shown($get) && ! $mixed($get));
                    }

                    $components[] = Grid::make(3)
                        ->schema([
                            $part("{$base}.length", $label.' — '.($listed ? 'min. uzunluq' : 'uzunluq'), $lengths)
                                ->required($required),
                            $part("{$base}.length_max", 'maks. uzunluq', $lengths)
                                ->required(fn (callable $get): bool => $required($get) && $mixed($get))
                                ->visible($mixed),
                            $part("{$base}.width", 'en', $widths)
                                ->required($required),
                        ])
                        ->visible(fn (callable $get): bool => $shown($get) && (! $listed || $mixed($get)))
                        ->columnSpanFull();
                    break;

                case 'decimal':
                case 'numeric':
                    // A list makes it a choice from the list, nothing typed.
                    $components[] = ($f['presets'] !== []
                        ? Forms\Components\Select::make("{$base}.value")->options(self::choices($f['presets']))
                        : Forms\Components\TextInput::make("{$base}.value"))
                        ->label($label)
                        ->required($required)
                        ->helperText($f['hint'])
                        ->visible($shown);
                    break;

                case 'textarea':
                    $components[] = Forms\Components\Textarea::make("{$base}.value")
                        ->label($label)->rows(2)
                        ->required($required)
                        ->helperText($f['hint'])
                        ->visible($shown)
                        ->columnSpanFull();
                    break;

                default:
                    $components[] = Forms\Components\TextInput::make("{$base}.value")
                        ->label($label)->maxLength(255)
                        ->required($required)
                        ->helperText($f['hint'])
                        ->visible($shown);
            }
        }

        return $components;
    }

    /** The text an option asks for («Digər» → exact name, «Bəli» → source). */
    private static function promptText(array $f, string $base, \Closure $shown, string $path): Forms\Components\TextInput
    {
        $prompt = function (callable $get) use ($f, $path): ?string {
            $picked = array_map('intval', Arr::wrap($get($path)));
            $option = collect($f['options'])->first(fn ($o) => $o['text_prompt'] && in_array($o['id'], $picked, true));

            return $option['text_prompt'] ?? null;
        };

        return Forms\Components\TextInput::make("{$base}.text")
            ->label(fn (callable $get) => $f['name'].': '.mb_strtolower((string) $prompt($get)))
            ->maxLength(255)
            ->required(fn (callable $get): bool => $shown($get) && $prompt($get) !== null)
            ->visible(fn (callable $get): bool => $shown($get) && $prompt($get) !== null);
    }

    /** @return array<int, string> */
    private static function options(array $f, array $answers): array
    {
        return collect($f['options'])
            ->filter(fn ($o) => CardSchema::holds($o['visible_when'], $answers))
            ->mapWithKeys(fn ($o) => [$o['id'] => $o['value']])
            ->all();
    }

    /** @return array<string, list<string>> */
    private static function answers(array $schema, callable $get): array
    {
        $answers = [];

        foreach ($schema['fields'] as $f) {
            if ($f['type'] === 'dropdown') {
                $ids = [(int) $get("card.{$f['id']}.option")];
            } elseif ($f['type'] === 'multiselect') {
                $ids = array_map('intval', Arr::wrap($get("card.{$f['id']}.options")));
            } else {
                continue;
            }

            $answers[$f['slug']] = collect($f['options'])->whereIn('id', $ids)->pluck('slug')->values()->all();
        }

        return $answers;
    }

    /**
     * Select options for listed numbers, keyed the way the stored value is filled
     * back ("8", "2.5" — CardProduct::trim()).
     *
     * @return array<string, string>
     */
    private static function choices(array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn ($v) => [(string) CardProduct::trim(str_replace(',', '.', (string) $v)) => str_replace('.', ',', (string) CardProduct::trim(str_replace(',', '.', (string) $v)))])
            ->all();
    }

    // ── Persistence ────────────────────────────────────────────────────────────

    /** Extra fill state for the edit page. */
    public static function fillState(Product $product): array
    {
        $schema = self::schema($product->sub_category_id);

        if (! $schema) {
            return [];
        }

        return [
            'card' => CardProduct::formState($product, $schema),
            'price_basis' => $schema['sale']['type'] === CardSchema::SALE_BOARD ? $product->unit : null,
        ];
    }

    /**
     * Validate the card (strict — an admin save is a publish-level save) and fold
     * the computed product columns into the data. Returns [data, rows to store];
     * rows are null when the product's class has no card.
     *
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>|null}
     */
    public static function beforeSave(array $data): array
    {
        $card = Arr::pull($data, 'card') ?? [];
        $basis = Arr::pull($data, 'price_basis');
        $schema = self::schema($data['sub_category_id'] ?? null);

        if (! $schema) {
            return [$data, null];
        }

        $check = CardProduct::check($schema, $data + ['price_basis' => $basis], (array) $card, true);

        if ($check['errors'] !== []) {
            throw ValidationException::withMessages(collect($check['errors'])
                ->mapWithKeys(fn ($message, $key) => [self::statePath($schema, (string) $key) => $message])
                ->all());
        }

        $columns = $check['columns'];
        $data = array_merge($data, $columns);

        if ($columns['is_unbranded']) {
            $data['brand_id'] = null;
        }
        if ($columns['quote_only']) {
            $data['price'] = 0;
            $data['old_price'] = null;
        }
        if ($columns['availability'] !== Product::IN_STOCK) {
            $data['stock'] = 0;
        }

        return [$data, $check['rows']];
    }

    public static function afterSave(Product $product, ?array $rows): void
    {
        if ($rows !== null) {
            CardProduct::saveValues($product, $rows);
        }
    }

    /** Validator key → Filament state path, so the message lands under its field. */
    private static function statePath(array $schema, string $key): string
    {
        if (preg_match('/^card\.(\d+)(\.text)?$/', $key, $m)) {
            if (! empty($m[2])) {
                return "data.card.{$m[1]}.text";
            }

            $field = collect($schema['fields'])->firstWhere('id', (int) $m[1]) ?? ['type' => 'text', 'presets' => []];

            return 'data.card.'.$m[1].'.'.match ($field['type']) {
                'dropdown' => 'option',
                'multiselect' => 'options',
                'size' => $field['presets'] !== [] ? 'pair' : 'length',
                default => 'value',
            };
        }

        return 'data.'.$key;
    }
}
