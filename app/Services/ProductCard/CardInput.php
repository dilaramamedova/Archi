<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

/**
 * Validates a submitted card against its schema and turns it into
 * product_attribute_values rows plus the product columns the card drives.
 *
 * Input shape (seller form `card[...]`, admin state `card`), per field id:
 *   dropdown     [option => id, text => '…']
 *   multiselect  [options => [id…], text => '…']
 *   decimal/text [value => '…']
 *   size         [length => …, length_max => …, width => …]
 *
 * Strict (publishing, admin save): required fields, "only" answers and the text an
 * option asks for must be there. A draft skips those and checks only what was typed.
 * Hidden fields (their condition does not hold) are neither validated nor stored.
 */
final class CardInput
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var list<string> */
    private array $missing = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    /** @var array<string, mixed> */
    private array $mapped = [];

    /** @var array<string, float> numeric answers by field slug, for lt/lte */
    private array $numbers = [];

    /**
     * @param  array<string, mixed>  $schema  CardSchema::for()
     * @param  array<int|string, mixed>  $input
     */
    public function __construct(
        private readonly array $schema,
        private readonly array $input,
        private readonly bool $strict,
        private readonly string $errorPrefix = 'card',
    ) {}

    /**
     * @return array{errors: array<string, string>, missing: list<string>, rows: list<array<string, mixed>>, mapped: array<string, mixed>, answers: array<string, list<string>>}
     */
    public function run(): array
    {
        $answers = $this->answers();

        foreach ($this->schema['fields'] as $field) {
            if (! CardSchema::holds($field['visible_when'], $answers)) {
                continue;
            }

            $this->field($field, (array) ($this->input[$field['id']] ?? []), $answers);
        }

        // Cross-field comparisons run once every number is known.
        foreach ($this->schema['fields'] as $field) {
            $this->compare($field, $answers);
        }

        return [
            'errors' => $this->errors,
            'missing' => $this->missing,
            'rows' => $this->rows,
            'mapped' => $this->mapped,
            'answers' => $answers,
        ];
    }

    /**
     * Option answers by field slug, read before validation so a field's condition can
     * look at a field rendered after it.
     *
     * @return array<string, list<string>>
     */
    private function answers(): array
    {
        $answers = [];

        foreach ($this->schema['fields'] as $field) {
            if (! in_array($field['type'], ['dropdown', 'multiselect'], true)) {
                continue;
            }

            $raw = (array) ($this->input[$field['id']] ?? []);
            $ids = $field['type'] === 'dropdown'
                ? [(int) ($raw['option'] ?? 0)]
                : array_map('intval', (array) ($raw['options'] ?? []));

            $answers[$field['slug']] = collect($field['options'])
                ->whereIn('id', $ids)
                ->pluck('slug')
                ->values()
                ->all();
        }

        return $answers;
    }

    private function field(array $field, array $raw, array $answers): void
    {
        $key = $this->errorPrefix.'.'.$field['id'];
        $name = $field['name'];

        match ($field['type']) {
            'dropdown', 'multiselect' => $this->choice($field, $raw, $answers, $key, $name),
            'size' => $this->size($field, $raw, $answers, $key, $name),
            'decimal', 'numeric' => $this->number($field, $raw, $key, $name),
            'document' => $this->document($field, $raw, $key, $name),
            default => $this->text($field, $raw, $key, $name),
        };
    }

    private function choice(array $field, array $raw, array $answers, string $key, string $name): void
    {
        $multi = $field['type'] === 'multiselect';
        $ids = $multi
            ? array_values(array_unique(array_filter(array_map('intval', (array) ($raw['options'] ?? [])))))
            : array_filter([(int) ($raw['option'] ?? 0)]);

        if ($ids === []) {
            $this->absent($field, $key, $name);

            return;
        }

        $options = collect($field['options'])->keyBy('id');
        $picked = [];

        foreach ($ids as $id) {
            $option = $options->get($id);

            // Unknown id, or an option that does not exist for the other answers
            // (e.g. «içiboş» for a solid-wood deck board).
            if (! $option || ! CardSchema::holds($option['visible_when'], $answers)) {
                $this->errors[$key] = CardText::get('invalid_option', ':name üçün yanlış seçim', ['name' => $name]);

                return;
            }

            $picked[] = $option;
        }

        if ($multi && count($picked) > 1 && collect($picked)->contains('exclusive', true)) {
            $this->errors[$key] = CardText::get('exclusive', ':name: «Göstərilməyib» başqa seçimlə birlikdə seçilə bilməz', ['name' => $name]);

            return;
        }

        $only = (array) ($field['rules']['only'] ?? []);
        if ($this->strict && $only !== [] && collect($picked)->pluck('slug')->diff($only)->isNotEmpty()) {
            $allowed = collect($field['options'])->whereIn('slug', $only)->pluck('value')->implode(' / ');
            $this->errors[$key] = CardText::get('only', ':name: bu kateqoriyada yalnız «:allowed» cavabı olan məhsullar yerləşdirilir', ['name' => $name, 'allowed' => $allowed]);

            return;
        }

        // One text box per field, asked for by whichever picked option needs it
        // («Digər» → the exact name, «Bəli» → the manufacturer's source).
        $text = trim((string) ($raw['text'] ?? ''));
        $prompted = collect($picked)->first(fn ($o) => $o['text_prompt'] !== null);

        if ($prompted && $text === '' && $this->strict) {
            $this->errors[$key.'.text'] = CardText::get('text_required', ':name: «:option» seçilibsə, :prompt', [
                'name' => $name, 'option' => $prompted['value'], 'prompt' => mb_strtolower($prompted['text_prompt']),
            ]);

            return;
        }

        if (mb_strlen($text) > 255) {
            $this->errors[$key.'.text'] = CardText::get('text_long', ':name çox uzundur', ['name' => $name]);

            return;
        }

        foreach ($picked as $i => $option) {
            $this->rows[] = [
                'attribute_id' => $field['id'],
                'attribute_option_id' => $option['id'],
                // The typed text rides on the option that asked for it.
                'value_text' => $prompted && $option['id'] === $prompted['id'] && $text !== '' ? $text : null,
            ];
        }

        if ($field['maps_to'] === 'package_unit') {
            $this->mapped['package_unit'] = CardSchema::UNIT_BY_OPTION[$picked[0]['slug']] ?? null;
        }
    }

    private function number(array $field, array $raw, string $key, string $name): void
    {
        $value = $this->decimal($raw['value'] ?? null);

        if ($value === null) {
            if (trim((string) ($raw['value'] ?? '')) !== '') {
                $this->errors[$key] = CardText::get('numeric', ':name rəqəm olmalıdır', ['name' => $name]);

                return;
            }
            $this->absent($field, $key, $name);

            return;
        }

        if (! $this->inBounds($field, $value, $key, $name)) {
            return;
        }

        // «Tam rəqəm»: counts (pipes through one element, suspension points in a kit).
        if (! empty($field['rules']['integer']) && abs($value - round($value)) > 0.00001) {
            $this->errors[$key] = CardText::get('integer', ':name tam rəqəm olmalıdır', ['name' => $name]);

            return;
        }

        // A field with a list is a choice from that list, not a free number.
        if ($field['presets'] !== [] && ! $this->listed($value, $field['presets'])) {
            $this->notListed($key, $name);

            return;
        }

        $this->numbers[$field['slug']] = $value;
        $this->rows[] = ['attribute_id' => $field['id'], 'value_numeric' => $value];

        if ($field['maps_to']) {
            $this->mapped[$field['maps_to']] = $value;
        }
    }

    private function size(array $field, array $raw, array $answers, string $key, string $name): void
    {
        $mixed = CardSchema::holds($field['rules']['mixed_when'] ?? ['attr' => '__never__'], $answers);

        // A listed size arrives as one "L×W" choice.
        if (! $mixed && trim((string) ($raw['pair'] ?? '')) !== '') {
            [$raw['length'], $raw['width']] = array_pad(CardSchema::splitSize((string) $raw['pair']), 2, null);
        }

        $length = $this->decimal($raw['length'] ?? null);
        $lengthMax = $mixed ? $this->decimal($raw['length_max'] ?? null) : null;
        $width = $this->decimal($raw['width'] ?? null);

        $typed = collect([$raw['length'] ?? null, $raw['width'] ?? null, $mixed ? ($raw['length_max'] ?? null) : null])
            ->filter(fn ($v) => trim((string) $v) !== '');

        if ($typed->isEmpty()) {
            $this->absent($field, $key, $name);

            return;
        }

        $complete = $length !== null && $width !== null && (! $mixed || $lengthMax !== null);

        if (! $complete) {
            // A half-typed size is always a mistake — even in a draft.
            $this->errors[$key] = CardText::get($mixed ? 'size_mixed' : 'size_incomplete',
                $mixed ? ':name: minimum uzunluq, maksimum uzunluq və en yazılmalıdır' : ':name: uzunluq və en yazılmalıdır',
                ['name' => $name]);

            return;
        }

        foreach ([$length, $lengthMax, $width] as $v) {
            if ($v !== null && ! $this->inBounds($field, $v, $key, $name)) {
                return;
            }
        }

        // With a list: a fixed size must be one of the listed pairs; a mixed-length
        // range picks its lengths and its width from the listed ones.
        if ($field['presets'] !== []) {
            [$lengths, $widths] = CardSchema::sizeParts($field['presets']);
            $ok = $mixed
                ? $this->listed($length, $lengths) && $this->listed($lengthMax, $lengths) && $this->listed($width, $widths)
                : collect($field['presets'])->contains(function ($pair) use ($length, $width) {
                    [$l, $w] = array_pad(CardSchema::splitSize((string) $pair), 2, null);

                    return $l !== null && $w !== null && abs((float) $l - $length) < 0.0001 && abs((float) $w - $width) < 0.0001;
                });

            if (! $ok) {
                $this->notListed($key, $name);

                return;
            }
        }

        if ($lengthMax !== null && $lengthMax < $length) {
            $this->errors[$key] = CardText::get('size_order', ':name: maksimum uzunluq minimumdan kiçik ola bilməz', ['name' => $name]);

            return;
        }

        if (CardSchema::holds($field['rules']['square_when'] ?? ['attr' => '__never__'], $answers) && abs($length - $width) > 0.00001) {
            $this->errors[$key] = CardText::get('size_square', ':name: kvadrat formada uzunluq və en bərabər olmalıdır', ['name' => $name]);

            return;
        }

        $this->rows[] = [
            'attribute_id' => $field['id'],
            'value_min' => $length,
            'value_max' => $lengthMax,
            'value_numeric' => $width,
        ];

        $this->mapped['length_mm'] ??= $length;
        $this->mapped['width_mm'] ??= $width;
    }

    /**
     * A document is a stored file path (the controller has already put an upload on
     * the public disk under product-docs/) or an https link. Anything else — another
     * scheme, a path outside product-docs/ — is refused.
     */
    private function document(array $field, array $raw, string $key, string $name): void
    {
        $value = trim((string) ($raw['value'] ?? ''));

        if ($value === '') {
            $this->absent($field, $key, $name);

            return;
        }

        $isFile = str_starts_with($value, 'product-docs/') && ! str_contains($value, '..');
        $isLink = filter_var($value, FILTER_VALIDATE_URL) && str_starts_with(strtolower($value), 'https://') && mb_strlen($value) <= 500;

        if (! $isFile && ! $isLink) {
            $this->errors[$key] = CardText::get('document_invalid', ':name: fayl yükləyin və ya https:// ilə başlayan link yazın', ['name' => $name]);

            return;
        }

        $this->rows[] = ['attribute_id' => $field['id'], 'value_text' => $value];
    }

    private function text(array $field, array $raw, string $key, string $name): void
    {
        $value = trim((string) ($raw['value'] ?? ''));

        if ($value === '') {
            $this->absent($field, $key, $name);

            return;
        }

        $limit = $field['type'] === 'textarea' ? 2000 : 255;
        if (mb_strlen($value) > $limit) {
            $this->errors[$key] = CardText::get('text_long', ':name çox uzundur', ['name' => $name]);

            return;
        }

        $this->rows[] = ['attribute_id' => $field['id'], 'value_text' => $value];
    }

    private function compare(array $field, array $answers): void
    {
        $mine = $this->numbers[$field['slug']] ?? null;
        if ($mine === null || ! CardSchema::holds($field['visible_when'], $answers)) {
            return;
        }

        foreach (['lt' => fn ($a, $b) => $a < $b, 'lte' => fn ($a, $b) => $a <= $b] as $rule => $test) {
            $other = $field['rules'][$rule] ?? null;
            $theirs = $other ? ($this->numbers[$other] ?? null) : null;

            if ($theirs === null || $test($mine, $theirs)) {
                continue;
            }

            $otherName = collect($this->schema['fields'])->firstWhere('slug', $other)['name'] ?? $other;
            $this->errors[$this->errorPrefix.'.'.$field['id']] = $rule === 'lt'
                ? CardText::get('less_than', ':name «:other» dəyərindən kiçik olmalıdır', ['name' => $field['name'], 'other' => $otherName])
                : CardText::get('not_greater', ':name «:other» dəyərindən böyük ola bilməz', ['name' => $field['name'], 'other' => $otherName]);
        }
    }

    private function inBounds(array $field, float $value, string $key, string $name): bool
    {
        $min = $field['rules']['min'] ?? null;
        $max = $field['rules']['max'] ?? 1000000;

        if ($value <= 0 || ($min !== null && $value < (float) $min) || $value > (float) $max) {
            $this->errors[$key] = CardText::get('out_of_range', ':name üçün dəyər düzgün deyil', ['name' => $name]);

            return false;
        }

        return true;
    }

    /** @param  list<int|float|string>  $list */
    private function listed(?float $value, array $list): bool
    {
        return $value !== null && collect($list)->contains(fn ($v) => abs((float) str_replace(',', '.', (string) $v) - $value) < 0.0001);
    }

    private function notListed(string $key, string $name): void
    {
        $this->errors[$key] = CardText::get('not_in_list', ':name: siyahıdakı dəyərlərdən birini seçin', ['name' => $name]);
    }

    private function absent(array $field, string $key, string $name): void
    {
        if (! $field['required']) {
            return;
        }

        $this->missing[] = $name;

        if ($this->strict) {
            $this->errors[$key] = CardText::get('required', '«:name» doldurulmalıdır', ['name' => $name]);
        }
    }

    /** "2,131" and "2.131" alike; anything else is not a number. */
    private function decimal(mixed $raw): ?float
    {
        $text = str_replace([',', ' '], ['.', ''], trim((string) $raw));

        return $text !== '' && is_numeric($text) ? round((float) $text, 4) : null;
    }
}
