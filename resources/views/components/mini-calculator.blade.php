{{--
  Homepage mini calculator — a popover hanging off the header's "Təmir kalkulyatoru"
  item, opened on every load so a first-time visitor meets the calculator before they
  scroll anything.

  It is the /calculator page in miniature and quotes from the same six database
  prices: area, renovation type, price segment. Nothing is approximated here and no
  parameter is assumed away, because the quick calculator has only three inputs to
  begin with (TZ §1) — a teaser that dropped one of them could not agree with the
  page it links to.

  The chips are built in JS from the price matrix shipped on data-quick, so a package
  the admin has not priced simply does not appear as a choice.
--}}
@php
    $quick = app(\App\Services\Calculator\QuickCalculator::class)->payload();

    $miniLabels = [
        'resultLabel' => t('calculator.result.label', ['area' => '{area}']),
        'currency' => t('calculator.result.currency_plain'),
        'fromSuffix' => t('calculator.result.currency'),
        'type' => [
            'WHITE_BOX' => t('calculator.type.white_box'),
            'FULL_RENOVATION' => t('calculator.type.full_renovation'),
        ],
        'segment' => [
            'ECONOMY' => t('calculator.level.economy'),
            'STANDARD' => t('calculator.level.standard'),
            'PREMIUM' => t('calculator.level.premium'),
        ],
        'errors' => [
            'AREA_REQUIRED' => t('calculator.errors.required'),
            'AREA_MUST_BE_POSITIVE' => t('calculator.errors.positive'),
            'AREA_BELOW_MINIMUM' => t('calculator.errors.min', ['min' => $quick['limits']['minArea']]),
            'AREA_ABOVE_MAXIMUM' => t('calculator.errors.max'),
            'PRICE_NOT_AVAILABLE' => t('calculator.errors.unavailable'),
        ],
    ];
@endphp

<div class="mcalc" id="miniCalc" role="dialog" aria-labelledby="miniCalcTitle" tabindex="-1" hidden
     data-quick="{{ json_encode($quick, JSON_UNESCAPED_UNICODE) }}"
     data-labels="{{ json_encode($miniLabels, JSON_UNESCAPED_UNICODE) }}">

  <button type="button" class="mcalc-x" id="miniCalcClose" aria-label="{{ t('home.mini_calc.close_aria') }}">
    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true" class="size-4">
      <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </button>

  <div class="mcalc-head">
    <span class="mcalc-tag"><img src="/assets/icon-calculator.svg" alt="">{{ t('calculator.head.tag') }}</span>
    <h2 class="mcalc-title" id="miniCalcTitle">{{ t('home.mini_calc.title') }}</h2>
    <p class="mcalc-sub">{{ t('home.mini_calc.subtitle') }}</p>
  </div>

  <div class="mcalc-field">
    <label class="mcalc-label" for="miniCalcArea">{{ t('calculator.area.label') }}</label>
    <div class="mcalc-input">
      {{-- min/max mirror the admin-configured bounds; inputmode="decimal" because the
           field accepts 12.5 and prices it correctly — "numeric" hides the separator
           key on a phone. --}}
      <input id="miniCalcArea" type="number" inputmode="decimal"
             min="{{ $quick['limits']['minArea'] }}" max="{{ $quick['limits']['maxArea'] }}"
             value="{{ $quick['defaults']['area'] }}">
      <span>{{ t('calculator.area.unit') }}</span>
    </div>
  </div>

  <div class="mcalc-field">
    <span class="mcalc-label" id="miniCalcTypeLbl">{{ t('calculator.type.label') }}</span>
    <div class="mcalc-chips" data-mini-key="type" role="group" aria-labelledby="miniCalcTypeLbl"></div>
  </div>

  <div class="mcalc-field">
    <span class="mcalc-label" id="miniCalcLevelLbl">{{ t('calculator.level.label') }}</span>
    <div class="mcalc-chips" data-mini-key="segment" role="group" aria-labelledby="miniCalcLevelLbl"></div>
  </div>

  {{-- aria-live so the price is announced when a chip changes it, not only seen --}}
  <div class="mcalc-result" aria-live="polite">
    <span class="mcalc-rlbl" id="miniCalcResultLabel"></span>
    <span class="mcalc-price"><b id="miniCalcPrice">—</b><i id="miniCalcCurrency"></i></span>
  </div>

  <p class="mcalc-note">{{ t('home.mini_calc.note') }}</p>

  <x-ui.button variant="primary" id="miniCalcCta" :href="route('calculator')"
               class="mcalc-cta">{{ t('home.mini_calc.cta') }}</x-ui.button>
</div>
