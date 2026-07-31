<x-layout page="business-onboarding-step3" :title="__('business-onboarding-step3.title')" bodyClass="bg-gray-soft2 text-ink [word-break:break-word]">

{{-- Head — Figma 1105:22591 (1440x128). Gray background comes from <body>. --}}
<div class="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-7 pt-8 pb-5">
  <a class="text-sm font-medium leading-[normal] whitespace-pre text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step3.head.back') }}</a>
  <h1 class="text-[32px] font-bold tracking-[-0.5px] leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.head.title') }}</h1>
</div>

{{-- Content — Figma 1105:22594 --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pb-5">
  <div class="flex w-[880px] shrink-0 flex-col items-start gap-2.5">

    {{-- Stepper — Figma 1105:22596 (880x72: padding 20 + active circle 32).
         Square corners, so it is not <x-ui.card>. --}}
    <div class="flex w-[880px] shrink-0 items-center justify-between border border-black/10 bg-white p-5">
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow-line">
          <p class="text-xs font-bold leading-[normal] whitespace-nowrap text-ink">✓</p>
        </div>
        <p class="text-sm font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.stepper.step1') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow-line">
          <p class="text-xs font-bold leading-[normal] whitespace-nowrap text-ink">✓</p>
        </div>
        <p class="text-sm font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.stepper.step2') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2.5">
        <div class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-pill bg-ink">
          <p class="text-sm font-semibold leading-[normal] whitespace-nowrap text-white">3</p>
        </div>
        <p class="text-[13px] font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.stepper.step3') }}</p>
      </div>
    </div>

    {{-- Form card — Figma 1105:22608 --}}
    <x-ui.card class="flex shrink-0 flex-col items-start gap-5 px-8 py-7">

      <x-ui.field variant="b2b" tag="p" :label="__('business-onboarding-step3.form.images_label')"
                  class="w-full shrink-0 items-start overflow-hidden bg-white">
        <div class="flex shrink-0 items-start gap-2.5 overflow-hidden bg-white">
          <div class="flex size-[110px] shrink-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-ds border border-dashed border-black/14 bg-gray-soft2 text-center text-xs font-medium text-black/50">
            <p class="leading-[normal] whitespace-nowrap">📷</p>
            <p class="leading-[normal] whitespace-nowrap">{{ __('business-onboarding-step3.form.images_main') }}</p>
          </div>
          <div class="flex size-[110px] shrink-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-ds border border-dashed border-black/14 bg-gray-soft2">
            <p class="text-center text-xl font-medium leading-[normal] whitespace-nowrap text-black/50">+</p>
          </div>
          <div class="flex size-[110px] shrink-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-ds border border-dashed border-black/14 bg-gray-soft2">
            <p class="text-center text-xl font-medium leading-[normal] whitespace-nowrap text-black/50">+</p>
          </div>
          <div class="flex size-[110px] shrink-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-ds border border-dashed border-black/14 bg-gray-soft2">
            <p class="text-center text-xl font-medium leading-[normal] whitespace-nowrap text-black/50">+</p>
          </div>
        </div>
      </x-ui.field>

      <div class="flex w-full shrink-0 items-start gap-4 bg-white">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.name_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" :placeholder="__('business-onboarding-step3.form.name_placeholder')" />
        </x-ui.field>

        <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.category_label')"
                    class="relative min-w-px flex-[1_0_0] items-start bg-white">
          {{-- Border width is reserved in the closed state; open state only changes the colour.
               Padding compensates the 1.5px border so the box matches the 1px fields next to it. --}}
          <div class="flex w-full shrink-0 cursor-pointer items-center justify-between overflow-hidden rounded-ds border-[1.5px] border-black/14 bg-white px-[15.5px] py-[12.5px] data-[on=true]:border-ink" data-cat-trigger data-on="false">
            <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/40 data-[filled=true]:text-ink" data-cat-value>{{ __('business-onboarding-step3.form.category_placeholder') }}</p>
            <p class="text-xs leading-[normal] text-black/50" data-cat-caret>▾</p>
          </div>

          {{-- Category menu — anchored to the trigger, scrolls when the list grows --}}
          <div class="absolute top-[calc(100%+4px)] left-0 z-20 flex max-h-[300px] w-full flex-col items-start overflow-y-auto rounded-ds border border-black/10 bg-white shadow-[0px_4px_16px_0px_rgba(0,0,0,0.08)] data-[on=false]:hidden" data-cat-menu data-on="false">
            @foreach (__('business-onboarding-step3.categories') as $category)
              <div class="group flex h-[38px] w-full shrink-0 cursor-pointer items-center justify-between overflow-hidden bg-white px-4 py-2.5 data-[sel=true]:bg-sel-bg"
                   data-cat-option="{{ $category }}" data-sel="false">
                <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/70 group-data-[sel=true]:font-medium group-data-[sel=true]:text-ink">{{ $category }}</p>
                <p class="hidden text-[13px] font-bold leading-[normal] whitespace-nowrap text-ink group-data-[sel=true]:block">✓</p>
              </div>
            @endforeach
          </div>
        </x-ui.field>
      </div>

      <div class="flex w-full shrink-0 items-start gap-0 overflow-hidden bg-white">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.brand_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" :placeholder="__('business-onboarding-step3.form.brand_placeholder')" />
        </x-ui.field>
      </div>

      <div class="flex w-full shrink-0 items-start gap-4 overflow-hidden bg-white">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.price_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" :placeholder="__('business-onboarding-step3.form.price_placeholder')" />
        </x-ui.field>

        {{-- Unit picker: a static box, not a <select> — the control stays page-local. --}}
        <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.unit_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
            <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/40">{{ __('business-onboarding-step3.form.unit_placeholder') }}</p>
            <p class="text-xs leading-[normal] text-black/50">▾</p>
          </div>
        </x-ui.field>
      </div>

      <x-ui.field variant="b2b" :label="__('business-onboarding-step3.form.desc_label')"
                  class="w-full shrink-0 items-start overflow-hidden bg-white">
        <x-ui.input variant="b2b" :placeholder="__('business-onboarding-step3.form.desc_placeholder')" />
      </x-ui.field>

      <div class="flex w-full shrink-0 items-center gap-2.5 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg px-3.5 py-3">
        <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black">🎉</p>
        <p class="min-w-px flex-[1_0_0] text-[13px] font-normal leading-[normal] text-black/70">{{ __('business-onboarding-step3.form.note') }}</p>
      </div>

      <div class="flex shrink-0 items-center gap-3 overflow-hidden bg-white">
        {{-- Last step of the funnel: completing the store lands on the shop panel. --}}
        <x-ui.button variant="primary" :hover="false" :href="route('business.profile')" class="items-start px-7 py-[15px]">
          <p class="font-sans text-[15px] font-semibold leading-[normal] whitespace-pre text-ink">{{ __('business-onboarding-step3.form.submit') }}</p>
        </x-ui.button>
        <p class="cursor-pointer text-sm font-medium leading-[normal] whitespace-nowrap text-black/50">{{ __('business-onboarding-step3.form.later') }}</p>
      </div>
    </x-ui.card>
  </div>

  {{-- Side — Figma 1105:22682 (472x215) --}}
  <div class="flex min-w-px flex-[1_0_0] flex-col items-start gap-4 overflow-hidden bg-white">
    <x-ui.card class="flex shrink-0 flex-col items-start gap-3 overflow-hidden p-5">
      <p class="text-sm font-semibold leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.side.progress_title') }}</p>
      {{-- Two stacked fills with a 50% opacity tail — not <x-ui.progress> (3px cabinet bar). --}}
      <div class="relative h-1.5 w-[280px] shrink-0 overflow-hidden rounded-ds bg-black/8">
        <div class="absolute top-0 left-0 h-1.5 w-[210px] rounded-ds bg-yellow"></div>
        <div class="absolute top-0 left-[210px] h-1.5 w-[70px] rounded-ds bg-yellow-line opacity-50"></div>
      </div>
      <p class="w-full text-[13px] font-normal leading-[normal] text-black/50">{{ __('business-onboarding-step3.side.progress_note') }}</p>
    </x-ui.card>
    <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg p-4 text-[13px]">
      <p class="font-semibold leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step3.side.tip_title') }}</p>
      <p class="w-full leading-[1.5] font-normal text-black/70">{{ __('business-onboarding-step3.side.tip_text') }}</p>
    </div>
  </div>
</div>

</x-layout>
