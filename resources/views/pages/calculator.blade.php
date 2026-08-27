{{--
  Quick renovation calculator (TZ "Sürətli təmir kalkulyatoru", v1.0).

  Three inputs and one multiplication: area × the price per m² of the chosen package.
  Nothing else belongs here — room counts, materials, cities and contact fields are
  the detailed calculator's job (TZ §48), and adding them back would turn a
  three-click price teaser into a questionnaire.

  The six prices, the area limits, the defaults and the CTA target all arrive from
  the database through CalculatorController::page(), so the first paint already shows
  a real number and an admin can change any of them without a deployment (TZ §30).
--}}
@php
    $labels = [
        'resultLabel' => t('calculator.result.label', ['area' => '{area}']),
        'currency' => t('calculator.result.currency_plain'),
        'fromSuffix' => t('calculator.result.currency'),
        'errors' => [
            'AREA_REQUIRED' => t('calculator.errors.required'),
            'AREA_MUST_BE_POSITIVE' => t('calculator.errors.positive'),
            'AREA_BELOW_MINIMUM' => t('calculator.errors.min', ['min' => $quick['limits']['minArea']]),
            'AREA_ABOVE_MAXIMUM' => t('calculator.errors.max'),
            'PRICE_NOT_AVAILABLE' => t('calculator.errors.unavailable'),
        ],
        'details' => [
            'area' => t('calculator.details.area'),
            'type' => t('calculator.details.type'),
            'segment' => t('calculator.details.segment'),
            'pricePerM2' => t('calculator.details.price_per_m2'),
            'total' => t('calculator.details.total'),
        ],
        'types' => [
            'WHITE_BOX' => t('calculator.type.white_box'),
            'FULL_RENOVATION' => t('calculator.type.full_renovation'),
        ],
        'segments' => [
            'ECONOMY' => t('calculator.level.economy'),
            'STANDARD' => t('calculator.level.standard'),
            'PREMIUM' => t('calculator.level.premium'),
        ],
        'whatsapp' => t('calculator.messages.whatsapp', ['area' => '{area}', 'level' => '{level}', 'price' => '{price}']),
        'saved' => t('calculator.messages.saved'),
    ];
@endphp
<x-layout page="calculator" :title="t('calculator.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-12 pb-20 max-[640px]:pt-4 max-[640px]:pb-10">
  <div class="wrap"><div class="inner max-[640px]:px-0">
    <x-ui.breadcrumbs class="qc-crumbs mb-7 max-[640px]:mb-4" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => t('calculator.breadcrumb')],
    ]" />

    <div class="mx-auto flex max-w-[620px] flex-col gap-5 rounded-ds border border-black/10 bg-white p-7 max-[640px]:px-5 max-[640px]:py-6"
         id="qcCalc"
         data-quick="{{ json_encode($quick, JSON_UNESCAPED_UNICODE) }}"
         data-labels="{{ json_encode($labels, JSON_UNESCAPED_UNICODE) }}">

      <div>
        <x-ui.eyebrow variant="b2b" class="[&_.rule]:w-7" :label="t('calculator.head.tag')" />
        <h1 class="mt-2 text-2xl font-bold tracking-[-.3px] text-ink">{{ t('calculator.head.title') }}</h1>
        <div class="mt-0.5 text-sm text-black/50">{{ t('calculator.head.subtitle') }}</div>
      </div>

      {{-- Step 1 — area. inputmode="decimal" so a phone opens the numeric keypad and
           still offers the separator: 12.5 m² is a valid answer. --}}
      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink" for="qcArea">{{ t('calculator.area.label') }}</label>
        <div class="flex items-center gap-1.5 rounded-ds border border-black/14 px-4 py-3 transition-[border-color] duration-200 focus-within:border-ink"
             id="qcAreaBox">
          <input class="w-full border-none text-xl font-bold text-ink outline-none [background:none] [font-family:inherit]"
                 id="qcArea" type="number" inputmode="decimal"
                 min="{{ $quick['limits']['minArea'] }}" max="{{ $quick['limits']['maxArea'] }}"
                 value="{{ $quick['defaults']['area'] }}"
                 aria-describedby="qcAreaErr">
          <span class="text-sm text-black/50">{{ t('calculator.area.unit') }}</span>
        </div>
        {{-- aria-live so a screen reader hears the validation message, not only sees it --}}
        <p class="hidden text-[13px] font-medium text-red-600" id="qcAreaErr" role="status" aria-live="polite"></p>
      </div>

      {{-- Step 2 — renovation type. A radiogroup, not decorative divs (TZ §44). --}}
      <div class="flex flex-col gap-2.5">
        <span class="text-[13px] font-semibold text-ink" id="qcTypeLbl">{{ t('calculator.type.label') }}</span>
        <div class="grid grid-cols-2 gap-2" data-key="type" role="radiogroup" aria-labelledby="qcTypeLbl">
          <button type="button" class="qc-seg" role="radio" data-v="WHITE_BOX">{{ t('calculator.type.white_box') }}</button>
          <button type="button" class="qc-seg" role="radio" data-v="FULL_RENOVATION">{{ t('calculator.type.full_renovation') }}</button>
        </div>
      </div>

      {{-- Step 3 — price segment --}}
      <div class="flex flex-col gap-2.5">
        <span class="text-[13px] font-semibold text-ink" id="qcSegLbl">{{ t('calculator.level.label') }}</span>
        <div class="grid grid-cols-3 gap-2" data-key="segment" role="radiogroup" aria-labelledby="qcSegLbl">
          <button type="button" class="qc-seg" role="radio" data-v="ECONOMY">{{ t('calculator.level.economy') }}</button>
          <button type="button" class="qc-seg" role="radio" data-v="STANDARD">{{ t('calculator.level.standard') }}</button>
          <button type="button" class="qc-seg" role="radio" data-v="PREMIUM">{{ t('calculator.level.premium') }}</button>
        </div>
      </div>

      <div class="my-0.5 h-px bg-black/10"></div>

      {{-- Result. The sum is the loudest element on the card (TZ §17, §40). --}}
      <div class="rounded-ds bg-sel-bg px-5 py-4" aria-live="polite">
        <div class="text-[13px] font-semibold text-black/50" id="qcRlbl"></div>
        <div class="mt-1 flex items-baseline gap-1.5" id="qcResult">
          <b class="text-[32px] font-bold leading-none tracking-[-.5px] text-ink max-[640px]:text-[28px]" id="qcPrice">—</b>
          <span class="text-sm font-medium text-black/50" id="qcCurrency"></span>
        </div>
        {{-- Secondary breakdown: present, but never competing with the sum (TZ §18) --}}
        <dl class="mt-3 hidden grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-[12px] text-black/50" id="qcDetails"></dl>
      </div>

      <p class="text-[12px] leading-[1.5] text-black/45">{{ t('calculator.disclaimer') }}</p>

      <x-ui.button variant="primary" id="qcNext" :href="route('calculator.detailed')"
                   class="h-[50px] gap-2 text-[15px] font-semibold duration-200 hover:brightness-[.93]">{{ t('calculator.actions.next') }}</x-ui.button>

      <div class="flex flex-wrap justify-center gap-5 max-[900px]:-my-2 max-[640px]:justify-between max-[640px]:gap-x-3 max-[640px]:gap-y-0">
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcPdf">{{ t('calculator.actions.pdf') }}</button>
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcWa">{{ t('calculator.actions.whatsapp') }}</button>
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcSave">{{ t('calculator.actions.save') }}</button>
      </div>
    </div>
  </div></div>
</section>

</x-layout>
