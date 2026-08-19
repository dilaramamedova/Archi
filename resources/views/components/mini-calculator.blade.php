{{--
  Homepage mini calculator — a popover hanging off the header's "Təmir kalkulyatoru"
  item, opened on every load so a first-time visitor meets the calculator before they
  scroll anything.

  Only a subset of the /calculator form is here, and the subset is chosen by how much a
  field moves the price: renovation type spans 0.55–1.25 and material level 600–1500 ₼/m²,
  so those two plus the area decide the number almost entirely. Object type and room
  count span ±10% each, stay at the page's own defaults (mənzil · 2 otaq) and are named
  in the footnote — the visitor is told what was assumed instead of being asked.

  The chips are built in JS from the shared price table (shared/calculator-pricing.js) so
  a multiplier can never be edited in one place and forgotten here; this file only ships
  their labels through data-labels. Positioning is JS too — the anchor is hidden below
  900px, see shared/mini-calculator.js.
--}}
@php
    // {token} placeholders are substituted in JS
    $miniLabels = [
        'resultLabel' => t('calculator.result.label', ['area' => '{area}']),
        'currency' => t('calculator.result.currency'),
        'type' => [
            'shell' => t('calculator.type.shell'),
            'cosmetic' => t('calculator.type.cosmetic'),
            'major' => t('calculator.type.major'),
            'turnkey' => t('calculator.type.turnkey'),
        ],
        'level' => [
            'economy' => t('calculator.level.economy'),
            'standard' => t('calculator.level.standard'),
            'premium' => t('calculator.level.premium'),
        ],
    ];
@endphp

<div class="mcalc" id="miniCalc" role="dialog" aria-labelledby="miniCalcTitle" tabindex="-1" hidden
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
      {{-- max caps the estimate at a plausible object: without it 999999 m² quoted
           1 931 248 000 ₼, which reads as a broken calculator rather than a big job.
           inputmode="decimal" because the field accepts 12.5 and prices it correctly —
           "numeric" hides the separator key on a phone. --}}
      <input id="miniCalcArea" type="number" min="1" max="100000" inputmode="decimal" value="80">
      <span>{{ t('calculator.area.unit') }}</span>
    </div>
  </div>

  <div class="mcalc-field">
    <span class="mcalc-label" id="miniCalcTypeLbl">{{ t('calculator.type.label') }}</span>
    <div class="mcalc-chips" data-mini-key="type" role="group" aria-labelledby="miniCalcTypeLbl"></div>
  </div>

  <div class="mcalc-field">
    <span class="mcalc-label" id="miniCalcLevelLbl">{{ t('calculator.level.label') }}</span>
    <div class="mcalc-chips" data-mini-key="level" role="group" aria-labelledby="miniCalcLevelLbl"></div>
  </div>

  {{-- aria-live so the price is announced when a chip changes it, not only seen --}}
  <div class="mcalc-result" aria-live="polite">
    <span class="mcalc-rlbl" id="miniCalcResultLabel"></span>
    <span class="mcalc-price"><b id="miniCalcPrice">—</b><i>{{ t('calculator.result.currency') }}</i></span>
  </div>

  <p class="mcalc-note">{{ t('home.mini_calc.note') }}</p>

  <x-ui.button variant="primary" id="miniCalcCta" :href="route('calculator')"
               class="mcalc-cta">{{ t('home.mini_calc.cta') }}</x-ui.button>
</div>
