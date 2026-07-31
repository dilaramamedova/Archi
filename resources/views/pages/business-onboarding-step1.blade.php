<x-layout page="business-onboarding-step1" :title="__('business-onboarding-step1.title')" bodyClass="bg-gray-soft2 [word-break:break-word]">

{{-- Head — Figma 1105:21878 (1440x128, pad 32/28/20/28) --}}
<div class="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-7 pt-8 pb-5">
  <a class="shrink-0 whitespace-pre text-sm font-medium leading-[normal] text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step1.back') }}</a>
  <h1 class="shrink-0 whitespace-nowrap text-[32px] font-bold leading-[normal] tracking-[-0.5px] text-ink">{{ __('business-onboarding-step1.heading') }}</h1>
</div>

{{-- Content — Figma 1105:21881 (pad 0/28/20/28, gap 32). Gray background comes from <body>. --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pb-5">
  <div class="flex w-[880px] shrink-0 flex-col items-start gap-2.5">

    {{-- Stepper — Figma 1105:21883 (880x68: pad 20 + 28 circle) --}}
    <div class="flex w-full items-center justify-between border border-black/10 bg-white p-5">
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

    {{-- Form card — Figma 1105:21898 (pad 28/32, r4, stroke black@0.10) --}}
    <div class="flex w-full flex-col items-start gap-5 overflow-hidden rounded-ds border border-black/10 bg-white px-8 py-7">
      <div class="flex w-full items-start gap-4 overflow-hidden bg-white">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.legal_name_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.legal_name_placeholder') }}</p>
          </div>
        </div>
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.brand_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.brand_placeholder') }}</p>
          </div>
        </div>
      </div>

      <div class="flex w-full items-start gap-0 overflow-hidden bg-white">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.tax_id_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.tax_id_placeholder') }}</p>
          </div>
        </div>
      </div>

      <div class="flex w-full items-start gap-4 overflow-hidden bg-white">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.city_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.city_placeholder') }}</p>
            <p class="shrink-0 text-xs leading-[normal] text-black/50">▾</p>
          </div>
        </div>
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-2 overflow-hidden bg-white">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.address_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.address_placeholder') }}</p>
          </div>
        </div>
      </div>

      {{-- Compact fields — stroke black@0.10, pad 12/14 --}}
      <div class="flex w-full items-start gap-4 overflow-hidden bg-white">
        <div class="relative flex h-[63px] min-w-px flex-[1_0_0] flex-col items-start gap-1.5">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.phone_label') }}</p>
          <div class="flex w-full shrink-0 items-start rounded-ds border border-black/10 px-3.5 py-3">
            <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-[#808085]">{{ __('business-onboarding-step1.form.phone_placeholder') }}</p>
          </div>
        </div>
        <div class="flex min-w-px flex-[1_0_0] items-start">
          <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-1.5">
            <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.showroom_label') }}</p>
            <div class="flex w-full shrink-0 items-start rounded-ds border border-black/10 px-3.5 py-3">
              <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-[#808085]">{{ __('business-onboarding-step1.form.showroom_placeholder') }}</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Upload zone — DS: radius 0, dashed black@0.25, pad 22 --}}
      <div class="flex w-full flex-none items-start">
        <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-1.5">
          <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.logo_label') }}</p>
          <div class="flex w-full shrink-0 items-center justify-center border border-dashed border-black/25 p-[22px]">
            <p class="shrink-0 whitespace-pre text-sm font-semibold leading-[normal] text-[#595966]">{{ __('business-onboarding-step1.form.logo_upload') }}</p>
          </div>
        </div>
      </div>

      <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden bg-white">
        <p class="shrink-0 whitespace-nowrap text-[13px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.form.about_label') }}</p>
        <div class="flex h-24 w-full shrink-0 items-start overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
          <p class="min-w-px flex-[1_0_0] text-sm font-normal leading-[normal] text-black/40">{{ __('business-onboarding-step1.form.about_placeholder') }}</p>
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
  <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-4 overflow-hidden bg-white">
    <div class="flex w-full shrink-0 flex-col items-start gap-3 overflow-hidden rounded-ds border border-black/10 bg-white p-5">
      <p class="shrink-0 whitespace-nowrap text-sm font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step1.side.progress_title') }}</p>
      <div class="relative h-1.5 w-[280px] shrink-0 overflow-hidden rounded-ds bg-black/8">
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
