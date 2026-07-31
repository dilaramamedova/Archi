<x-layout page="business-onboarding-step1" :title="__('business-onboarding-step1.title')" bodyClass="bg-gray-soft2 [word-break:break-word]">

{{-- Head — Figma 1105:21878 (1440x128, pad 32/28/20/28) --}}
<div class="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-7 pt-8 pb-5">
  <a class="shrink-0 whitespace-pre text-sm font-medium leading-[normal] text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step1.back') }}</a>
  <h1 class="shrink-0 whitespace-nowrap text-[32px] font-bold leading-[normal] tracking-[-0.5px] text-ink">{{ __('business-onboarding-step1.heading') }}</h1>
</div>

{{-- Content — Figma 1105:21881 (pad 0/28/20/28, gap 32). Gray background comes from <body>.
     The Figma frame is a fixed 1440 layout; below 1200 the two columns stack so the side
     column is never squeezed to a sliver. Nothing here changes the rendering at 1440px. --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pb-5 max-[1200px]:flex-col">
  <div class="flex w-full max-w-[880px] flex-col items-start gap-2.5 max-[1200px]:max-w-none">

    {{-- Stepper — Figma 1105:21883 (880x68: pad 20 + 28 circle) --}}
    <div class="flex w-full items-center justify-between border border-black/10 bg-white p-5 max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-3">
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-ink">
          <p class="whitespace-nowrap text-[13px] font-bold leading-[normal] text-white">1</p>
        </div>
        <p class="whitespace-nowrap text-sm font-bold leading-[normal] text-ink">{{ __('business-onboarding-step1.steps.company') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e5e8ed]">
          <p class="whitespace-nowrap text-[13px] font-bold leading-[normal] text-[#808085]">2</p>
        </div>
        <p class="whitespace-nowrap text-sm font-medium leading-[normal] text-[#808085]">{{ __('business-onboarding-step1.steps.contact') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e5e8ed]">
          <p class="whitespace-nowrap text-[13px] font-bold leading-[normal] text-[#808085]">3</p>
        </div>
        <p class="whitespace-nowrap text-sm font-medium leading-[normal] text-[#808085]">{{ __('business-onboarding-step1.steps.product') }}</p>
      </div>
    </div>

    {{-- Form card — Figma 1105:21898 (pad 28/32, r4, stroke black@0.10).
         No overflow-hidden: the city menu is absolutely positioned and must escape the card. --}}
    <div class="flex w-full flex-col items-start gap-5 rounded-ds border border-black/10 bg-white px-8 py-7">
      <div class="flex w-full items-start gap-4 overflow-hidden bg-white max-[640px]:flex-col">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white max-[640px]:w-full">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-legal-name">{{ __('business-onboarding-step1.form.legal_name_label') }}</label>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-black/40" id="onb1-legal-name" type="text" name="legal_name" autocomplete="organization" placeholder="{{ __('business-onboarding-step1.form.legal_name_placeholder') }}">
          </div>
        </div>
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white max-[640px]:w-full">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-brand">{{ __('business-onboarding-step1.form.brand_label') }}</label>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-black/40" id="onb1-brand" type="text" name="brand" placeholder="{{ __('business-onboarding-step1.form.brand_placeholder') }}">
          </div>
        </div>
      </div>

      <div class="flex w-full items-start gap-0 overflow-hidden bg-white">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-tax-id">{{ __('business-onboarding-step1.form.tax_id_label') }}</label>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-black/40" id="onb1-tax-id" type="text" name="tax_id" inputmode="numeric" maxlength="10" placeholder="{{ __('business-onboarding-step1.form.tax_id_placeholder') }}">
          </div>
        </div>
      </div>

      <div class="flex w-full items-start gap-4 bg-white max-[640px]:flex-col">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 bg-white max-[640px]:w-full">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" id="onb1-city-label">{{ __('business-onboarding-step1.form.city_label') }}</p>
          {{-- Border width is reserved in the closed state; the open state only changes the
               colour. Padding compensates the 1.5px border so the box matches the 1px fields. --}}
          <div class="flex w-full shrink-0 cursor-pointer items-center justify-between overflow-hidden rounded-ds border-[1.5px] border-black/14 bg-white px-[15.5px] py-[12.5px] data-[on=true]:border-ink"
               data-city-trigger data-on="false" role="combobox" tabindex="0" aria-expanded="false" aria-haspopup="listbox" aria-labelledby="onb1-city-label">
            <p class="whitespace-nowrap text-sm font-normal leading-[normal] text-black/40 data-[filled=true]:text-ink" data-city-value>{{ __('business-onboarding-step1.form.city_placeholder') }}</p>
            <p class="shrink-0 text-xs leading-[normal] text-black/50" data-city-caret>▾</p>
          </div>
          <input type="hidden" name="city" data-city-field value="">

          {{-- City menu — anchored to the trigger, scrolls when the list grows --}}
          <div class="absolute top-[calc(100%+4px)] left-0 z-20 flex max-h-[300px] w-full flex-col items-start overflow-y-auto rounded-ds border border-black/10 bg-white shadow-[0px_4px_16px_0px_rgba(0,0,0,0.08)] data-[on=false]:hidden"
               data-city-menu data-on="false" role="listbox" aria-labelledby="onb1-city-label">
            @foreach (__('business-onboarding-step1.cities') as $city)
              <div class="group flex h-[38px] w-full shrink-0 cursor-pointer items-center justify-between overflow-hidden bg-white px-4 py-2.5 data-[sel=true]:bg-sel-bg"
                   data-city-option="{{ $city }}" data-sel="false" role="option" aria-selected="false">
                <p class="whitespace-nowrap text-sm font-normal leading-[normal] text-black/70 group-data-[sel=true]:font-medium group-data-[sel=true]:text-ink">{{ $city }}</p>
                <p class="hidden shrink-0 text-[13px] font-bold leading-[normal] text-ink group-data-[sel=true]:block">✓</p>
              </div>
            @endforeach
          </div>
        </div>
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white max-[640px]:w-full">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-address">{{ __('business-onboarding-step1.form.address_label') }}</label>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-black/40" id="onb1-address" type="text" name="address" autocomplete="street-address" placeholder="{{ __('business-onboarding-step1.form.address_placeholder') }}">
          </div>
        </div>
      </div>

      {{-- Compact fields — stroke black@0.10, pad 12/14. Both halves are identical columns:
           no height clamp (the natural height is ~65px) and no extra wrapper, so they always
           end at the same y even when a label wraps. --}}
      <div class="flex w-full items-start gap-4 overflow-hidden bg-white max-[640px]:flex-col">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-1.5 max-[640px]:w-full">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-phone">{{ __('business-onboarding-step1.form.phone_label') }}</label>
          <div class="flex w-full shrink-0 items-start rounded-ds border border-black/10 px-3.5 py-3">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-[#808085]" id="onb1-phone" type="tel" name="phone" autocomplete="tel" placeholder="{{ __('business-onboarding-step1.form.phone_placeholder') }}">
          </div>
        </div>
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-1.5 max-[640px]:w-full">
          <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-showroom">{{ __('business-onboarding-step1.form.showroom_label') }}</label>
          <div class="flex w-full shrink-0 items-start rounded-ds border border-black/10 px-3.5 py-3">
            <input class="w-full border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-[#808085]" id="onb1-showroom" type="text" name="showroom" placeholder="{{ __('business-onboarding-step1.form.showroom_placeholder') }}">
          </div>
        </div>
      </div>

      {{-- Upload zone — DS: radius 0, dashed black@0.25, pad 22. The <label> wraps a visually
           hidden file input, so the whole dashed box is the picker. --}}
      <div class="flex w-full flex-none items-start">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-1.5">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.logo_label') }}</p>
          <label class="flex w-full shrink-0 cursor-pointer items-center justify-center border border-dashed border-black/25 p-[22px]" for="onb1-logo">
            <input class="sr-only" id="onb1-logo" type="file" name="logo" accept=".png,.svg,image/png,image/svg+xml" data-logo-input data-empty-label="{{ __('business-onboarding-step1.form.logo_upload') }}">
            <p class="min-w-0 max-w-full overflow-hidden text-ellipsis whitespace-pre text-sm font-semibold leading-[normal] text-[#595966]" data-logo-text>{{ __('business-onboarding-step1.form.logo_upload') }}</p>
          </label>
        </div>
      </div>

      <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden bg-white">
        <label class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink" for="onb1-about">{{ __('business-onboarding-step1.form.about_label') }}</label>
        <div class="flex h-24 w-full shrink-0 items-stretch overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
          <textarea class="min-w-px flex-[1_0_0] resize-none border-none bg-transparent font-sans text-sm font-normal leading-[normal] text-ink outline-none placeholder:text-black/40" id="onb1-about" name="about" placeholder="{{ __('business-onboarding-step1.form.about_placeholder') }}"></textarea>
        </div>
      </div>
    </div>

    {{-- Action bar — Figma 1105:21942 (880x96) --}}
    <div class="flex w-full shrink-0 flex-col items-start overflow-hidden rounded-ds border border-black/10 bg-white p-6">
      <div class="flex w-full items-center gap-3 overflow-hidden bg-white">
        <a class="flex shrink-0 items-center justify-center overflow-hidden rounded-ds bg-yellow px-7 py-[15px]" href="{{ route('business.onboarding.step3') }}">
          <p class="shrink-0 whitespace-pre text-[15px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.actions.save') }}</p>
        </a>
        <a class="shrink-0 whitespace-nowrap text-sm font-medium leading-[normal] text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step1.actions.later') }}</a>
      </div>
    </div>

  </div>

  {{-- Side column — Figma 1105:21947 (472x215) --}}
  <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-4 overflow-hidden bg-white max-[1200px]:w-full max-[1200px]:flex-none">
    <div class="flex w-full shrink-0 flex-col items-start gap-3 overflow-hidden rounded-ds border border-black/10 bg-white p-5">
      <p class="shrink-0 whitespace-nowrap text-sm font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.side.progress_title') }}</p>
      <div class="relative h-1.5 w-full max-w-[280px] shrink-0 overflow-hidden rounded-ds bg-black/8">
        <div class="absolute top-0 left-0 h-1.5 w-[70px] rounded-ds bg-yellow"></div>
        <div class="absolute top-0 left-[70px] h-1.5 w-[70px] rounded-ds bg-yellow-line opacity-50"></div>
      </div>
      <p class="w-full shrink-0 text-[13px] font-normal leading-[normal] text-black/50">{{ __('business-onboarding-step1.side.progress_note') }}</p>
    </div>
    <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg p-4 text-[13px]">
      <p class="shrink-0 whitespace-nowrap font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.side.tip_title') }}</p>
      <p class="w-full shrink-0 font-normal leading-[1.5] text-black/70">{{ __('business-onboarding-step1.side.tip_text') }}</p>
    </div>
  </div>

</div>

</x-layout>
