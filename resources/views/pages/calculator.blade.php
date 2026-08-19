{{--
  Quick calculator page, ported from the old static page. The navbar/footer
  placeholders are gone (the layout renders them); the tier cards are still built in JS,
  so their texts travel to resources/js/pages/calculator.js through data-labels.
--}}
@php
    // Strings the tier cards / action buttons need at runtime ({token} = JS placeholder)
    $calcLabels = [
        'resultLabel' => t('calculator.result.label', ['area' => '{area}']),
        'currency' => t('calculator.result.currency'),
        'recommended' => t('calculator.result.recommended'),
        'saved' => t('calculator.messages.saved'),
        'whatsapp' => t('calculator.messages.whatsapp', ['area' => '{area}', 'level' => '{level}', 'price' => '{price}']),
        'tiers' => [
            'economy' => t('calculator.level.economy'),
            'standard' => t('calculator.level.standard'),
            'premium' => t('calculator.level.premium'),
        ],
    ];
@endphp
<x-layout page="calculator" :title="t('calculator.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-12 pb-20 max-[640px]:pt-4 max-[640px]:pb-10">
  {{-- .wrap and .inner both drop to px-4 below 640px, which doubled the gutter to 32px;
       the frame asks for 16px, so the inner padding is dropped on mobile --}}
  <div class="wrap"><div class="inner max-[640px]:px-0">
    <x-ui.breadcrumbs class="qc-crumbs mb-7 max-[640px]:mb-4" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => t('calculator.breadcrumb')],
    ]" />

    <div class="mx-auto flex max-w-[620px] flex-col gap-5 rounded-ds border border-black/10 bg-white p-7 max-[640px]:px-5 max-[640px]:py-6"
         id="qcCalc"
         data-labels="{{ json_encode($calcLabels, JSON_UNESCAPED_UNICODE) }}">
      <div>
        {{-- the rule is 28px here, the b2b default is 32px --}}
        <x-ui.eyebrow variant="b2b" class="[&_.rule]:w-7" :label="t('calculator.head.tag')" />
        <h1 class="mt-2 text-2xl font-bold tracking-[-.3px] text-ink">{{ t('calculator.head.title') }}</h1>
        <div class="mt-0.5 text-sm text-black/50">{{ t('calculator.head.subtitle') }}</div>
      </div>

      {{-- chip groups — the selected state is the data-on attribute --}}
      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ t('calculator.object.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="obj">
          <button class="qc-chip" data-v="apartment" data-m="1" data-on="true">{{ t('calculator.object.apartment') }}</button>
          <button class="qc-chip" data-v="house" data-m="1.1">{{ t('calculator.object.house') }}</button>
          <button class="qc-chip" data-v="office" data-m="0.95">{{ t('calculator.object.office') }}</button>
          <button class="qc-chip" data-v="commercial" data-m="1.05">{{ t('calculator.object.commercial') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ t('calculator.area.label') }}</label>
        <div class="flex items-center gap-1.5 rounded-ds border border-black/14 px-4 py-3 transition-[border-color] duration-200 focus-within:border-ink">
          {{-- max matches the mini panel's cap — see components/mini-calculator.blade.php --}}
          <input class="w-full border-none text-xl font-bold text-ink outline-none [background:none] [font-family:inherit]" id="qcArea" type="number" min="1" max="100000" inputmode="decimal" value="80">
          <span class="text-sm text-black/50">{{ t('calculator.area.unit') }}</span>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ t('calculator.type.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="type">
          <button class="qc-chip" data-v="shell" data-m="0.55">{{ t('calculator.type.shell') }}</button>
          <button class="qc-chip" data-v="cosmetic" data-m="0.7">{{ t('calculator.type.cosmetic') }}</button>
          <button class="qc-chip" data-v="major" data-m="1" data-on="true">{{ t('calculator.type.major') }}</button>
          <button class="qc-chip" data-v="turnkey" data-m="1.25">{{ t('calculator.type.turnkey') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ t('calculator.rooms.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="rooms">
          <button class="qc-chip" data-v="studio" data-m="0.95">{{ t('calculator.rooms.studio') }}</button>
          <button class="qc-chip" data-v="1" data-m="1">{{ t('calculator.rooms.one') }}</button>
          <button class="qc-chip" data-v="2" data-m="1.03" data-on="true">{{ t('calculator.rooms.two') }}</button>
          <button class="qc-chip" data-v="3" data-m="1.06">{{ t('calculator.rooms.three') }}</button>
          <button class="qc-chip" data-v="4" data-m="1.1">{{ t('calculator.rooms.four_plus') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ t('calculator.level.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="level">
          <button class="qc-chip" data-v="economy">{{ t('calculator.level.economy') }}</button>
          <button class="qc-chip" data-v="standard" data-on="true">{{ t('calculator.level.standard') }}</button>
          <button class="qc-chip" data-v="premium">{{ t('calculator.level.premium') }}</button>
        </div>
      </div>

      <div class="my-0.5 h-px bg-black/10"></div>

      <div class="text-[13px] font-semibold text-black/50" id="qcRlbl">{{ t('calculator.result.label', ['area' => 80]) }}</div>
      <div class="flex gap-3 max-[640px]:flex-col max-[640px]:gap-2.5" id="qcTiers"></div>

      <x-ui.button variant="primary" id="qcNext" :href="route('calculator.detailed')"
                   class="h-[50px] gap-2 text-[15px] font-semibold duration-200 hover:brightness-[.93]">{{ t('calculator.actions.next') }}</x-ui.button>
      {{-- the three actions are one justified line in the frame; below 380px they still
           wrap rather than shrink the gap to nothing. `.qc-act` carries the 44px touch
           box below 900px (resources/css/pages/calculator.css) — the text itself is
           unchanged, so `-my-2` keeps the taller boxes from stretching the card's rhythm --}}
      <div class="flex flex-wrap justify-center gap-5 max-[900px]:-my-2 max-[640px]:justify-between max-[640px]:gap-x-3 max-[640px]:gap-y-0">
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcPdf">{{ t('calculator.actions.pdf') }}</button>
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcWa">{{ t('calculator.actions.whatsapp') }}</button>
        <button class="qc-act cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcSave">{{ t('calculator.actions.save') }}</button>
      </div>
    </div>
  </div></div>
</section>

</x-layout>
