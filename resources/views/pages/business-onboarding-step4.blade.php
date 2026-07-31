<x-layout page="business-onboarding-step4" :title="__('business-onboarding-step4.title')" bodyClass="text-ink [word-break:break-word]">

{{-- Figma 1105:21287 — the first-product screen with the category listbox OPEN.
     That open state is what this frame documents, so the markup ships open and the
     page module takes over from the first interaction. --}}

{{-- Head — Figma 1105:21289 (1440x108). The page background is white in this frame
     (sampled from the Figma render), same as step 2, so no gray bodyClass tint. --}}
<div class="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-7 pt-8">
  <a class="text-sm font-medium leading-[normal] whitespace-pre text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step4.head.back') }}</a>
  <h1 class="text-[32px] font-bold tracking-[-0.5px] leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.head.title') }}</h1>
</div>

{{-- Content — Figma 1105:21292 (main 880 + gap 32 + side 472) --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pt-8 pb-12">
  <div class="flex w-[880px] shrink-0 flex-col items-start gap-2.5">

    {{-- Stepper — Figma 1105:21294 (880x72: padding 20 + active circle 32).
         Square corners, so it is not <x-ui.card>. --}}
    <div class="flex w-[880px] shrink-0 items-center justify-between border border-black/10 bg-white p-5">
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow-line">
          <p class="text-xs font-bold leading-[normal] whitespace-nowrap text-ink">✓</p>
        </div>
        <p class="text-sm font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.stepper.step1') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow-line">
          <p class="text-xs font-bold leading-[normal] whitespace-nowrap text-ink">✓</p>
        </div>
        <p class="text-sm font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.stepper.step2') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2.5">
        <div class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-pill bg-ink">
          <p class="text-sm font-semibold leading-[normal] whitespace-nowrap text-white">3</p>
        </div>
        <p class="text-[13px] font-medium leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.stepper.step3') }}</p>
      </div>
    </div>

    {{-- Form card — Figma 1105:21306 (880x666). Every control is 43px here, two less
         than .ui-control-b2b's 13px padding gives, so the four inputs and the two custom
         boxes carry a 12px vertical padding as a markup utility (later layer than
         @layer components, so it wins without a specificity hack). --}}
    <x-ui.card class="flex shrink-0 flex-col items-start gap-5 px-8 py-7">

      <x-ui.field variant="b2b" tag="p" :label="__('business-onboarding-step4.form.images_label')"
                  class="w-full shrink-0 items-start overflow-hidden bg-white">
        <div class="flex shrink-0 items-start gap-2.5 overflow-hidden bg-white">
          <div class="flex size-[110px] shrink-0 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-ds border border-dashed border-black/14 bg-gray-soft2 text-center text-xs font-medium text-black/50">
            {{-- Own colour: the tile's black/50 would render the colour emoji at half
                 opacity, washing it out against the tile. --}}
            <p class="leading-[normal] whitespace-nowrap text-black">📷</p>
            <p class="leading-[normal] whitespace-nowrap">{{ __('business-onboarding-step4.form.images_main') }}</p>
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
        <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.name_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" class="py-3" :placeholder="__('business-onboarding-step4.form.name_placeholder')" />
        </x-ui.field>

        {{-- Category combobox — a JS-driven listbox, so it stays page-local (§4.7). --}}
        <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.category_label')"
                    class="relative min-w-px flex-[1_0_0] items-start bg-white">
          {{-- The 1.5px border is reserved in both states; only the colour changes, and the
               padding compensates it (12 - 0.5) so the box lines up with the 1px fields
               next to it and lands on the same 43px height. --}}
          <div class="group flex w-full shrink-0 cursor-pointer items-center justify-between overflow-hidden rounded-ds border-[1.5px] border-black/14 bg-white px-[15.5px] py-[11.5px] data-[on=true]:border-ink"
               data-cat-trigger data-on="true" role="combobox" aria-expanded="true" aria-haspopup="listbox">
            <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/40 data-[filled=true]:text-ink" data-cat-value>{{ __('business-onboarding-step4.form.category_placeholder') }}</p>
            <img class="size-4 shrink-0 transition-transform duration-200 group-data-[on=true]:rotate-180" src="/assets/ic-caret.svg" alt="">
          </div>

          {{-- Listbox — Figma 1105:21354. Width is content-driven (157px in az); the tick is
               absolutely placed so selecting an item never reflows the menu. --}}
          {{-- items-stretch (not items-start) so a row's tint spans the whole menu while
               w-max still sizes it to its widest label. The tick is always in flow and only
               `invisible` when unselected, so its column is reserved for every locale and
               selecting a long category never reflows the menu or collides with the label. --}}
          <div class="absolute top-[calc(100%+3px)] left-0 z-20 flex max-h-[456px] w-max flex-col items-stretch overflow-y-auto rounded-ds border border-black/10 bg-white shadow-[0px_4px_16px_0px_rgba(0,0,0,0.08)] data-[on=false]:hidden"
               data-cat-menu data-on="true" role="listbox">
            @foreach (__('business-onboarding-step4.categories') as $category)
              <div class="group flex h-[38px] shrink-0 cursor-pointer items-center justify-between gap-3 bg-white px-4 py-2.5 data-[sel=true]:bg-sel-bg"
                   data-cat-option="{{ $category }}" data-sel="{{ $loop->index === 1 ? 'true' : 'false' }}"
                   role="option" aria-selected="{{ $loop->index === 1 ? 'true' : 'false' }}">
                <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/70 group-data-[sel=true]:font-medium group-data-[sel=true]:text-ink">{{ $category }}</p>
                <p class="invisible text-[13px] font-bold leading-[normal] whitespace-nowrap text-ink group-data-[sel=true]:visible">✓</p>
              </div>
            @endforeach
          </div>
        </x-ui.field>
      </div>

      <div class="flex w-full shrink-0 items-start gap-0 overflow-hidden bg-white">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.brand_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" class="py-3" :placeholder="__('business-onboarding-step4.form.brand_placeholder')" />
        </x-ui.field>
      </div>

      <div class="flex w-full shrink-0 items-start gap-4 overflow-hidden bg-white">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.price_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <x-ui.input variant="b2b" class="py-3" :placeholder="__('business-onboarding-step4.form.price_placeholder')" />
        </x-ui.field>

        {{-- Unit picker: a static box in this frame, not a <select>. --}}
        <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.unit_label')"
                    class="min-w-px flex-[1_0_0] items-start overflow-hidden bg-white">
          <div class="flex w-full shrink-0 items-center justify-between overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-3">
            <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black/40">{{ __('business-onboarding-step4.form.unit_placeholder') }}</p>
            <img class="size-4 shrink-0" src="/assets/ic-caret.svg" alt="">
          </div>
        </x-ui.field>
      </div>

      <x-ui.field variant="b2b" :label="__('business-onboarding-step4.form.desc_label')"
                  class="w-full shrink-0 items-start overflow-hidden bg-white">
        <x-ui.input variant="b2b" class="py-3" :placeholder="__('business-onboarding-step4.form.desc_placeholder')" />
      </x-ui.field>

      {{-- Note strip — Figma 1105:21347 (816x40). Height is pinned, not padded: the 🎉
           emoji raises the line box above the text's own, so vertical padding alone
           would overshoot the 40px spec. --}}
      <div class="flex h-10 w-full shrink-0 items-center gap-2.5 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg px-3.5">
        <p class="text-sm font-normal leading-[normal] whitespace-nowrap text-black">🎉</p>
        <p class="min-w-px flex-[1_0_0] text-[13px] font-normal leading-[normal] text-black/70">{{ __('business-onboarding-step4.form.note') }}</p>
      </div>

      <div class="flex shrink-0 items-center gap-3 overflow-hidden bg-white">
        {{-- Last step of the funnel: completing the store lands on the shop panel. --}}
        {{-- Figma 1105:21351 (367x48). Height is pinned for the same reason as the note
             strip: the trailing ✓ in the label raises the line box past 18px. --}}
        <x-ui.button variant="primary" :hover="false" :href="route('business.profile')" class="h-12 px-7">
          <p class="font-sans text-[15px] font-semibold leading-[normal] whitespace-pre text-ink">{{ __('business-onboarding-step4.form.submit') }}</p>
        </x-ui.button>
        <p class="cursor-pointer text-sm font-medium leading-[normal] whitespace-nowrap text-black/50">{{ __('business-onboarding-step4.form.later') }}</p>
      </div>
    </x-ui.card>
  </div>

  {{-- Side — Figma 1105:21380 (472x215) --}}
  <div class="flex min-w-px flex-[1_0_0] flex-col items-start gap-4 overflow-hidden bg-white">
    <x-ui.card class="flex shrink-0 flex-col items-start gap-3 overflow-hidden p-5">
      <p class="text-sm font-semibold leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.side.progress_title') }}</p>
      {{-- 75%: a 210px fill plus a 70px half-opacity tail — not <x-ui.progress> (3px cabinet bar). --}}
      <div class="relative h-1.5 w-[280px] shrink-0 overflow-hidden rounded-ds bg-black/8">
        <div class="absolute top-0 left-0 h-1.5 w-[210px] rounded-ds bg-yellow"></div>
        <div class="absolute top-0 left-[210px] h-1.5 w-[70px] rounded-ds bg-yellow-line opacity-50"></div>
      </div>
      <p class="w-full text-[13px] font-normal leading-[normal] text-black/50">{{ __('business-onboarding-step4.side.progress_note') }}</p>
    </x-ui.card>
    <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg p-4 text-[13px]">
      <p class="font-semibold leading-[normal] whitespace-nowrap text-ink">{{ __('business-onboarding-step4.side.tip_title') }}</p>
      <p class="w-full leading-[1.5] font-normal text-black/70">{{ __('business-onboarding-step4.side.tip_text') }}</p>
    </div>
  </div>
</div>

</x-layout>
