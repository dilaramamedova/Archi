{{--
  Quick calculator page, ported from the old static page. The navbar/footer
  placeholders are gone (the layout renders them); the tier cards are still built in JS,
  so their texts travel to resources/js/pages/calculator.js through data-labels.
--}}
@php
    // Strings the tier cards / action buttons need at runtime ({token} = JS placeholder)
    $calcLabels = [
        'resultLabel' => __('calculator.result.label', ['area' => '{area}']),
        'currency' => __('calculator.result.currency'),
        'recommended' => __('calculator.result.recommended'),
        'saved' => __('calculator.messages.saved'),
        'whatsapp' => __('calculator.messages.whatsapp', ['area' => '{area}', 'level' => '{level}', 'price' => '{price}']),
        'tiers' => [
            'economy' => __('calculator.level.economy'),
            'standard' => __('calculator.level.standard'),
            'premium' => __('calculator.level.premium'),
        ],
    ];
@endphp
<x-layout page="calculator" :title="__('calculator.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-12 pb-20">
  <div class="wrap"><div class="inner">
    <x-ui.breadcrumbs class="qc-crumbs mb-7" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('calculator.breadcrumb')],
    ]" />

    <div class="mx-auto flex max-w-[620px] flex-col gap-5 rounded-ds border border-black/10 bg-white p-7 max-[640px]:p-5"
         id="qcCalc"
         data-labels="{{ json_encode($calcLabels, JSON_UNESCAPED_UNICODE) }}">
      <div>
        {{-- the rule is 28px here, the b2b default is 32px --}}
        <x-ui.eyebrow variant="b2b" class="[&_.rule]:w-7" :label="__('calculator.head.tag')" />
        <h1 class="mt-2 text-2xl font-bold tracking-[-.3px] text-ink">{{ __('calculator.head.title') }}</h1>
        <div class="mt-0.5 text-sm text-black/50">{{ __('calculator.head.subtitle') }}</div>
      </div>

      {{-- chip groups — the selected state is the data-on attribute --}}
      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ __('calculator.object.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="obj">
          <button class="qc-chip" data-v="apartment" data-m="1" data-on="true">{{ __('calculator.object.apartment') }}</button>
          <button class="qc-chip" data-v="house" data-m="1.1">{{ __('calculator.object.house') }}</button>
          <button class="qc-chip" data-v="office" data-m="0.95">{{ __('calculator.object.office') }}</button>
          <button class="qc-chip" data-v="commercial" data-m="1.05">{{ __('calculator.object.commercial') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ __('calculator.area.label') }}</label>
        <div class="flex items-center gap-1.5 rounded-ds border border-black/14 px-4 py-3 transition-[border-color] duration-200 focus-within:border-ink">
          <input class="w-full border-none text-xl font-bold text-ink outline-none [background:none] [font-family:inherit]" id="qcArea" type="number" min="1" value="80">
          <span class="text-sm text-black/50">{{ __('calculator.area.unit') }}</span>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ __('calculator.type.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="type">
          <button class="qc-chip" data-v="shell" data-m="0.55">{{ __('calculator.type.shell') }}</button>
          <button class="qc-chip" data-v="cosmetic" data-m="0.7">{{ __('calculator.type.cosmetic') }}</button>
          <button class="qc-chip" data-v="major" data-m="1" data-on="true">{{ __('calculator.type.major') }}</button>
          <button class="qc-chip" data-v="turnkey" data-m="1.25">{{ __('calculator.type.turnkey') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ __('calculator.rooms.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="rooms">
          <button class="qc-chip" data-v="studio" data-m="0.95">{{ __('calculator.rooms.studio') }}</button>
          <button class="qc-chip" data-v="1" data-m="1">{{ __('calculator.rooms.one') }}</button>
          <button class="qc-chip" data-v="2" data-m="1.03" data-on="true">{{ __('calculator.rooms.two') }}</button>
          <button class="qc-chip" data-v="3" data-m="1.06">{{ __('calculator.rooms.three') }}</button>
          <button class="qc-chip" data-v="4" data-m="1.1">{{ __('calculator.rooms.four_plus') }}</button>
        </div>
      </div>

      <div class="flex flex-col gap-2.5">
        <label class="text-[13px] font-semibold text-ink">{{ __('calculator.level.label') }}</label>
        <div class="flex flex-wrap gap-2" data-key="level">
          <button class="qc-chip" data-v="economy">{{ __('calculator.level.economy') }}</button>
          <button class="qc-chip" data-v="standard" data-on="true">{{ __('calculator.level.standard') }}</button>
          <button class="qc-chip" data-v="premium">{{ __('calculator.level.premium') }}</button>
        </div>
      </div>

      <div class="my-0.5 h-px bg-black/10"></div>

      <div class="text-[13px] font-semibold text-black/50" id="qcRlbl">{{ __('calculator.result.label', ['area' => 80]) }}</div>
      <div class="flex gap-3 max-[640px]:flex-col" id="qcTiers"></div>

      <x-ui.button variant="primary" id="qcNext" :href="route('calculator.detailed')"
                   class="h-[50px] gap-2 text-[15px] font-semibold duration-200 hover:brightness-[.93]">{{ __('calculator.actions.next') }}</x-ui.button>
      <div class="flex flex-wrap justify-center gap-5">
        <button class="cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcPdf">{{ __('calculator.actions.pdf') }}</button>
        <button class="cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcWa">{{ __('calculator.actions.whatsapp') }}</button>
        <button class="cursor-pointer border-none text-[13px] font-medium text-black/40 [background:none] [font-family:inherit] hover:text-ink" id="qcSave">{{ __('calculator.actions.save') }}</button>
      </div>
    </div>
  </div></div>
</section>

</x-layout>
