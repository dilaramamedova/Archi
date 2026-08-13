{{--
  "Post a product" page. Ported from the old sell.html — the form is client-only, the
  listing is stored in localStorage and rendered back on the home page grid.
  Form controls come from the design system (<x-ui.field> / <x-ui.input>); sell's own
  15px type and 15/13 padding are passed as utilities. Only the condition chip
  (.sl-chip) and the modal keyframes stay in resources/css/pages/sell.css.
--}}
@php
    // $categories is passed from SellController (DB categories with translation fallback).
    // sell's control geometry: 15px text, 15/13 padding (the DS default is 16px, 16/14).
    // The 375 frame drops that to 14px text in a 44px-tall box, so the mobile step is
    // carried along on every control instead of being repeated field by field.
    $control = 'px-[15px] py-[13px] text-[15px] max-[560px]:px-3.5 max-[560px]:py-3 max-[560px]:text-sm';
@endphp
<x-layout page="sell" :title="t('sell.title')">

<section class="bg-gray-soft2 py-12 max-[560px]:pt-6 max-[560px]:pb-10">
  <div class="mx-auto max-w-[1040px] px-7 max-[560px]:px-4">
    <div class="mb-7 flex flex-col gap-2 max-[560px]:mb-4">
      <x-ui.eyebrow :label="t('sell.hero.tag')" />
      <h1 class="text-[32px] font-semibold tracking-[-.4px] text-black/90 max-[560px]:text-2xl">{{ t('sell.hero.title') }}</h1>
      <p class="max-w-[640px] text-base leading-[1.5] text-black/55 max-[560px]:text-[13px]">{{ t('sell.hero.subtitle') }}</p>
    </div>

    <form class="border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[560px]:px-4 max-[560px]:py-5" id="sellForm"
          data-url-home="{{ route('home') }}"
          data-url-sell="{{ route('sell') }}"
          data-url-store="{{ route('sell.store') }}"
          data-url-login="{{ route('login') }}"
          data-url-register="{{ route('register', ['role' => 'seller']) }}"
          data-l-currency="{{ t('sell.form.currency') }}"
          data-l-reviews-zero="{{ t('sell.product.reviews_zero') }}"
          data-l-view-site="{{ t('sell.success.view_site') }}"
          data-l-add-another="{{ t('sell.success.add_another') }}"
          data-l-sign-up="{{ t('sell.success.sign_up') }}"
          data-l-not-now="{{ t('sell.success.not_now') }}"
          data-l-image-required="{{ t('business-product-edit.save.error_image') }}"
          data-l-form-error="{{ t('sell.form.error') }}"
          data-l-cond-new="{{ t('sell.form.condition_new') }}"
          data-l-cond-used="{{ t('sell.form.condition_used') }}"
          data-l-server-error="{{ t('sell.form.server_error') }}">
      <div class="grid grid-cols-[360px_1fr] gap-8 max-[860px]:grid-cols-1 max-[560px]:gap-[18px]">
        {{-- image · the uploaded state is driven by the data-has attribute --}}
        <label class="group relative flex min-h-[360px] cursor-pointer items-center justify-center overflow-hidden border-2 border-dashed border-black/20 bg-gray-soft transition-[border-color,background] duration-200 hover:border-black hover:bg-[#eef1f4] max-[860px]:min-h-[240px] max-[560px]:min-h-[200px]" id="upBox">
          <input type="file" id="upInput" accept="image/*" hidden>
          <div class="pointer-events-none flex flex-col items-center gap-2.5 p-6 text-center group-data-[has=true]:hidden" id="upPh">
            <span class="flex size-14 items-center justify-center rounded-full bg-yellow max-[560px]:size-[52px]"><img class="size-[26px] max-[560px]:size-6" src="/assets/icon-plus.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink max-[560px]:text-[15px]">{{ t('sell.upload.title') }}</h4>
            <span class="text-[13px] text-black/50 max-[560px]:text-xs">{{ t('sell.upload.hint') }}</span>
          </div>
          <img class="absolute inset-0 size-full object-cover" id="upPrev" alt="" hidden>
          <button type="button" class="absolute top-2.5 right-2.5 z-[3] hidden size-[34px] items-center justify-center border-none bg-black text-xl text-white group-data-[has=true]:flex" id="upRm" aria-label="{{ t('sell.upload.remove') }}">&times;</button>
        </label>

        {{-- fields --}}
        <div class="flex flex-col gap-[18px]">
          <x-ui.field class="flex-1">
            {{-- the required marker is markup, so the label is written out instead of passed as a prop --}}
            <label class="ui-label" for="pName">{{ t('sell.form.name_label') }} <span class="text-red">*</span></label>
            <x-ui.input :class="$control" id="pName" :placeholder="t('sell.form.name_placeholder')" required />
          </x-ui.field>

          {{-- the frame shows the category full-width on mobile, so this pair keeps
               stacking — at the 18px field rhythm rather than the 14px row gap --}}
          <div class="flex gap-3.5 max-[560px]:flex-col max-[560px]:gap-[18px]">
            <x-ui.field class="flex-1">
              <label class="ui-label" for="pCat">{{ t('sell.form.category_label') }} <span class="text-red">*</span></label>
              {{-- Options rendered via slot: ui.select's :options treats int keys as a
                   plain list and would submit the label instead of the category id. --}}
              <x-ui.select :class="$control" id="pCat" :placeholder="t('sell.form.category_placeholder')" required>
                @foreach ($categories as $cat)
                  <option value="{{ $cat['id'] ?? '' }}">{{ $cat['name'] }}</option>
                @endforeach
              </x-ui.select>
            </x-ui.field>
            <x-ui.field class="flex-1">
              <span class="ui-label" id="pCondLbl">{{ t('sell.form.condition_label') }}</span>
              {{-- radiogroup · roving tabindex + arrow keys are wired in pages/sell.js --}}
              <div class="flex gap-2" id="pCond" role="radiogroup" aria-labelledby="pCondLbl">
                <button type="button" class="sl-chip" role="radio" aria-checked="true" tabindex="0" data-v="{{ t('sell.form.condition_new') }}" data-on="true">{{ t('sell.form.condition_new') }}</button>
                <button type="button" class="sl-chip" role="radio" aria-checked="false" tabindex="-1" data-v="{{ t('sell.form.condition_used') }}" data-on="false">{{ t('sell.form.condition_used') }}</button>
              </div>
            </x-ui.field>
          </div>

          {{-- price and old price stay side by side at 375 (two 150px boxes with a 12px
               gutter in the frame) — stacking them pushed the submit button below the fold --}}
          {{-- items-end: "Köhnə qiymət" wraps to two lines at 375 and the two controls
               would otherwise sit at different heights --}}
          <div class="flex items-end gap-3.5 max-[560px]:gap-3">
            <x-ui.field class="min-w-0 flex-1">
              <label class="ui-label" for="pPrice">{{ t('sell.form.price_label') }} <span class="text-red">*</span></label>
              {{-- min-w-0: a number input's intrinsic size (~20 chars) is its flex basis
                   floor, so without it the two price boxes cannot share a 375px row --}}
              <div class="relative flex items-center"><x-ui.input type="number" :class="$control . ' min-w-0 flex-1 pr-[38px] max-[560px]:pr-[30px]'" id="pPrice" placeholder="0" min="0" step="0.01" /><span class="pointer-events-none absolute right-3.5 text-[15px] text-black/45 max-[560px]:right-3 max-[560px]:text-[13px]">{{ t('sell.form.currency') }}</span></div>
            </x-ui.field>
            <x-ui.field class="min-w-0 flex-1" :label="t('sell.form.old_price_label')" for="pOld">
              <div class="relative flex items-center"><x-ui.input type="number" :class="$control . ' min-w-0 flex-1 pr-[38px] max-[560px]:pr-[30px]'" id="pOld" placeholder="0" min="0" step="0.01" /><span class="pointer-events-none absolute right-3.5 text-[15px] text-black/45 max-[560px]:right-3 max-[560px]:text-[13px]">{{ t('sell.form.currency') }}</span></div>
            </x-ui.field>
          </div>

          <x-ui.field class="flex-1" :label="t('sell.form.description_label')" for="pDesc">
            <x-ui.textarea :class="$control . ' min-h-24 resize-y leading-[1.5]'" id="pDesc" :placeholder="t('sell.form.description_placeholder')" />
          </x-ui.field>
        </div>
      </div>

      <div class="mt-7 flex items-center justify-between gap-5 border-t border-black/10 pt-6 max-[860px]:flex-col max-[860px]:items-stretch max-[560px]:mt-[18px] max-[560px]:gap-[18px] max-[560px]:pt-[18px]">
        {{-- items-start: at 375 the note wraps to two lines and centring it against the
             icon would leave the tick floating in the middle of the paragraph --}}
        <p class="flex max-w-[420px] items-center gap-2 text-[13px] leading-[1.4] text-black/55 max-[560px]:items-start max-[560px]:text-xs max-[560px]:leading-[1.5]"><span class="inline-flex size-[18px] shrink-0 items-center justify-center rounded-full bg-green/12"><img class="size-[11px]" src="/assets/icon-check-green.svg" alt=""></span> {{ t('sell.form.note') }}</p>
        <x-ui.button variant="primary" type="submit" id="pSubmit" class="h-[54px] rounded-none px-8 text-[17px] font-semibold whitespace-nowrap hover:brightness-[.93] max-[860px]:w-full max-[560px]:h-[52px] max-[560px]:text-base">{{ t('sell.form.submit') }}</x-ui.button>
      </div>
    </form>
  </div>
</section>

{{-- success + sign-up nudge · the fadeIn/lmIn keyframes live in pages/sell.css --}}
<x-ui.modal id="okOv" aria-labelledby="okTitle" :close-label="t('sell.success.close')"
            dialog="w-full max-w-[480px] animate-[lmIn_0.26s_ease] bg-white px-9 py-10 text-center shadow-[-6px_6px_28px_rgba(0,0,0,0.22)]">
  <div class="mx-auto mb-5 flex size-[72px] items-center justify-center rounded-full bg-green/12"><img class="size-[34px]" src="/assets/icon-check-green.svg" alt=""></div>
  <h2 class="mb-2.5 text-[26px] font-semibold text-ink" id="okTitle">{{ t('sell.success.title') }}</h2>
  <p class="mb-6 text-[15px] leading-[1.6] text-black/60">{{ t('sell.success.congrats') }} <b id="okName">{{ t('sell.success.default_name') }}</b> {{ t('sell.success.text_after') }}</p>

  <div class="mb-6 hidden border border-black/10 bg-gray-soft2 p-[18px] text-left" id="regNudge">
    <div class="mb-1.5 flex items-center gap-2 text-[15px] font-semibold text-ink"><img class="size-[18px]" src="/assets/icon-crown-gold.svg" alt="">{{ t('sell.success.nudge_title') }}</div>
    <p class="text-sm leading-[1.5] text-black/60">{{ t('sell.success.nudge_text') }}</p>
  </div>

  <div class="flex flex-col gap-2.5" id="okBtns"></div>
</x-ui.modal>

</x-layout>
