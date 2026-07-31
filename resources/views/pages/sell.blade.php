{{--
  "Post a product" page. Ported from the old sell.html — the form is client-only, the
  listing is stored in localStorage and rendered back on the home page grid.
  Repeated field/chip styles live in resources/css/pages/sell.css (.sl-in .sl-lbl .sl-chip).
--}}
@php
    $categories = [
        __('sell.categories.tiles'),
        __('sell.categories.paint'),
        __('sell.categories.plumbing'),
        __('sell.categories.electric'),
        __('sell.categories.laminate'),
        __('sell.categories.building'),
        __('sell.categories.decor'),
    ];
@endphp
<x-layout page="sell" :title="__('sell.title')">

<section class="bg-gray-soft2 py-12">
  <div class="mx-auto max-w-[1040px] px-7">
    <div class="mb-7 flex flex-col gap-2">
      <div class="flex items-center gap-3"><span class="h-1 w-8 rounded-[2px] bg-yellow"></span><p class="text-[13px] font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('sell.hero.tag') }}</p></div>
      <h1 class="text-[32px] font-semibold tracking-[-.4px] text-black/90 max-[560px]:text-[26px]">{{ __('sell.hero.title') }}</h1>
      <p class="max-w-[640px] text-base leading-[1.5] text-black/55">{{ __('sell.hero.subtitle') }}</p>
    </div>

    <form class="border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[560px]:p-[22px]" id="sellForm"
          data-url-home="{{ route('home') }}"
          data-url-sell="{{ route('sell') }}"
          data-url-register="{{ route('register', ['role' => 'seller']) }}"
          data-l-currency="{{ __('sell.form.currency') }}"
          data-l-reviews-zero="{{ __('sell.product.reviews_zero') }}"
          data-l-view-site="{{ __('sell.success.view_site') }}"
          data-l-add-another="{{ __('sell.success.add_another') }}"
          data-l-sign-up="{{ __('sell.success.sign_up') }}"
          data-l-not-now="{{ __('sell.success.not_now') }}">
      <div class="mb-[18px] hidden border border-red bg-[#fdeaea] px-[15px] py-3 text-sm text-[#b4322c]" id="slErr">{{ __('sell.form.error') }}</div>

      <div class="grid grid-cols-[360px_1fr] gap-8 max-[860px]:grid-cols-1">
        {{-- image · the uploaded state is driven by the data-has attribute --}}
        <label class="group relative flex min-h-[360px] cursor-pointer items-center justify-center overflow-hidden border-2 border-dashed border-black/20 bg-gray-soft transition-[border-color,background] duration-200 hover:border-black hover:bg-[#eef1f4] max-[860px]:min-h-[240px]" id="upBox">
          <input type="file" id="upInput" accept="image/*" hidden>
          <div class="pointer-events-none flex flex-col items-center gap-2.5 p-6 text-center group-data-[has=true]:hidden" id="upPh">
            <span class="flex size-14 items-center justify-center rounded-full bg-yellow"><img class="size-[26px]" src="/assets/ic-plus.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ __('sell.upload.title') }}</h4>
            <span class="text-[13px] text-black/50">{{ __('sell.upload.hint') }}</span>
          </div>
          <img class="absolute inset-0 size-full object-cover" id="upPrev" alt="" hidden>
          <button type="button" class="absolute top-2.5 right-2.5 z-[3] hidden size-[34px] items-center justify-center border-none bg-black text-xl text-white group-data-[has=true]:flex" id="upRm" aria-label="{{ __('sell.upload.remove') }}">&times;</button>
        </label>

        {{-- fields --}}
        <div class="flex flex-col gap-[18px]">
          <div class="flex flex-1 flex-col gap-2">
            <label class="sl-lbl" for="pName">{{ __('sell.form.name_label') }} <span class="text-red">*</span></label>
            <input class="sl-in" type="text" id="pName" placeholder="{{ __('sell.form.name_placeholder') }}" required>
          </div>

          <div class="flex gap-3.5 max-[560px]:flex-col">
            <div class="flex flex-1 flex-col gap-2">
              <label class="sl-lbl" for="pCat">{{ __('sell.form.category_label') }} <span class="text-red">*</span></label>
              <select class="sl-in" id="pCat" required>
                <option value="">{{ __('sell.form.category_placeholder') }}</option>
                @foreach ($categories as $category)
                  <option>{{ $category }}</option>
                @endforeach
              </select>
            </div>
            <div class="flex flex-1 flex-col gap-2">
              <span class="sl-lbl" id="pCondLbl">{{ __('sell.form.condition_label') }}</span>
              {{-- radiogroup · roving tabindex + arrow keys are wired in pages/sell.js --}}
              <div class="flex gap-2" id="pCond" role="radiogroup" aria-labelledby="pCondLbl">
                <button type="button" class="sl-chip" role="radio" aria-checked="true" tabindex="0" data-v="{{ __('sell.form.condition_new') }}" data-on="true">{{ __('sell.form.condition_new') }}</button>
                <button type="button" class="sl-chip" role="radio" aria-checked="false" tabindex="-1" data-v="{{ __('sell.form.condition_used') }}" data-on="false">{{ __('sell.form.condition_used') }}</button>
              </div>
            </div>
          </div>

          <div class="flex gap-3.5 max-[560px]:flex-col">
            <div class="flex flex-1 flex-col gap-2">
              <label class="sl-lbl" for="pPrice">{{ __('sell.form.price_label') }} <span class="text-red">*</span></label>
              <div class="relative flex items-center"><input class="sl-in flex-1 pr-[38px]" type="number" id="pPrice" placeholder="0" min="0" step="0.01"><span class="pointer-events-none absolute right-3.5 text-[15px] text-black/45">{{ __('sell.form.currency') }}</span></div>
            </div>
            <div class="flex flex-1 flex-col gap-2">
              <label class="sl-lbl" for="pOld">{{ __('sell.form.old_price_label') }}</label>
              <div class="relative flex items-center"><input class="sl-in flex-1 pr-[38px]" type="number" id="pOld" placeholder="0" min="0" step="0.01"><span class="pointer-events-none absolute right-3.5 text-[15px] text-black/45">{{ __('sell.form.currency') }}</span></div>
            </div>
          </div>

          <div class="flex flex-1 flex-col gap-2">
            <label class="sl-lbl" for="pDesc">{{ __('sell.form.description_label') }}</label>
            <textarea class="sl-in min-h-24 resize-y leading-[1.5]" id="pDesc" placeholder="{{ __('sell.form.description_placeholder') }}"></textarea>
          </div>
        </div>
      </div>

      <div class="mt-7 flex items-center justify-between gap-5 border-t border-black/10 pt-6 max-[860px]:flex-col max-[860px]:items-stretch">
        <p class="flex max-w-[420px] items-center gap-2 text-[13px] leading-[1.4] text-black/55"><span class="inline-flex size-[18px] shrink-0 items-center justify-center rounded-full bg-green/12"><img class="size-[11px]" src="/assets/ic-check.svg" alt=""></span> {{ __('sell.form.note') }}</p>
        <button class="h-[54px] cursor-pointer border-none bg-yellow px-8 text-[17px] font-semibold whitespace-nowrap text-ink transition-[filter] duration-200 hover:brightness-[.93] max-[860px]:w-full" type="submit" id="pSubmit">{{ __('sell.form.submit') }}</button>
      </div>
    </form>
  </div>
</section>

{{-- success + sign-up nudge · the fadeIn/lmIn keyframes live in pages/sell.css --}}
<div class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/55 p-6 backdrop-blur-[2px]" id="okOv" role="dialog" aria-modal="true" aria-labelledby="okTitle">
  <div class="relative w-full max-w-[480px] animate-[lmIn_0.26s_ease] bg-white px-9 py-10 text-center shadow-[-6px_6px_28px_rgba(0,0,0,0.22)]">
    <button type="button" class="absolute top-[14px] right-[14px] flex size-[34px] cursor-pointer items-center justify-center border border-black/15 bg-white text-[22px] leading-none text-[#333] transition-[background] duration-200 hover:bg-gray-soft" id="okClose" aria-label="{{ __('sell.success.close') }}">&times;</button>
    <div class="mx-auto mb-5 flex size-[72px] items-center justify-center rounded-full bg-green/12"><img class="size-[34px]" src="/assets/ic-check.svg" alt=""></div>
    <h2 class="mb-2.5 text-[26px] font-semibold text-ink" id="okTitle">{{ __('sell.success.title') }}</h2>
    <p class="mb-6 text-[15px] leading-[1.6] text-black/60">{{ __('sell.success.congrats') }} <b id="okName">{{ __('sell.success.default_name') }}</b> {{ __('sell.success.text_after') }}</p>

    <div class="mb-6 hidden border border-black/10 bg-gray-soft2 p-[18px] text-left" id="regNudge">
      <div class="mb-1.5 flex items-center gap-2 text-[15px] font-semibold text-ink"><img class="size-[18px]" src="/assets/ic-crown.svg" alt="">{{ __('sell.success.nudge_title') }}</div>
      <p class="text-sm leading-[1.5] text-black/60">{{ __('sell.success.nudge_text') }}</p>
    </div>

    <div class="flex flex-col gap-2.5" id="okBtns"></div>
  </div>
</div>

</x-layout>
