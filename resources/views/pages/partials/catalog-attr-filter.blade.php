{{--
  One dynamic catalog filter built from a class attribute (catalog classifier).
  Expects: $attribute (App\Models\Attribute with ->pivot from the class and
  ->options loaded), $activeAttrOptions, $activeAttrRanges (from CatalogController).

  Markup mirrors the existing sidebar controls: option lists reuse .fs-check,
  numeric ranges reuse .fs-price-inputs. catalog.js collects the state via the
  data-attr / data-opt / data-bool attributes and builds attr[...] URL params.
--}}
@php
    $slug = $attribute->slug;
    $unit = $attribute->pivot->unit ?? null;
    $tooltip = (string) $attribute->tooltip;
    $activeOptions = $activeAttrOptions[$slug] ?? [];
    $activeRange = $activeAttrRanges[$slug] ?? ['min' => null, 'max' => null];
@endphp
<div class="fs-block fs-attr" data-attr-block="{{ $slug }}">
  <p class="fs-title">
    <span class="t">{{ $attribute->name }}@if ($unit) <span class="unit">({{ $unit }})</span>@endif</span>
    @if ($tooltip !== '')
      <span class="fs-tip" tabindex="0" role="note" aria-label="{{ $tooltip }}">
        <span class="i">?</span>
        <span class="pop">{{ $tooltip }}</span>
      </span>
    @endif
  </p>

  @if ($attribute->field_type->hasOptions())
    @foreach ($attribute->options as $option)
      <div class="fs-check" data-attr="{{ $slug }}" data-opt="{{ $option->slug }}"
           data-on="{{ in_array($option->slug, $activeOptions, true) ? 'true' : 'false' }}">
        <span class="fside-box fs-box"></span><span class="lbl">{{ $option->value }}</span>
      </div>
    @endforeach
  @elseif ($attribute->field_type === \App\Enums\AttributeFieldType::Boolean)
    <div class="fs-check" data-attr="{{ $slug }}" data-bool="1"
         data-on="{{ in_array('1', $activeOptions, true) ? 'true' : 'false' }}">
      <span class="fside-box fs-box"></span><span class="lbl">{{ t('catalog-classifier.filters.bool_yes') }}</span>
    </div>
  @elseif (in_array($attribute->field_type, [\App\Enums\AttributeFieldType::Numeric, \App\Enums\AttributeFieldType::Decimal, \App\Enums\AttributeFieldType::Range], true))
    <div class="fs-price-inputs fs-attr-range" data-attr="{{ $slug }}">
      <input class="in" data-attr-min type="text" inputmode="decimal"
             placeholder="{{ t('catalog-classifier.filters.min') }}"
             value="{{ $activeRange['min'] !== null ? $activeRange['min'] + 0 : '' }}"
             aria-label="{{ $attribute->name }} — {{ t('catalog-classifier.filters.min') }}">
      <span class="dash">—</span>
      <input class="in" data-attr-max type="text" inputmode="decimal"
             placeholder="{{ t('catalog-classifier.filters.max') }}"
             value="{{ $activeRange['max'] !== null ? $activeRange['max'] + 0 : '' }}"
             aria-label="{{ $attribute->name }} — {{ t('catalog-classifier.filters.max') }}">
    </div>
  @endif
</div>
