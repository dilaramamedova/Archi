<x-layout page="business-onboarding-step2" :title="__('business-onboarding-step2.title')" bodyClass="[word-break:break-word]">

{{-- Head — Figma 1105:21045 (1440x108, pad 32/28/0/28, gap 20, white).
     Unlike step 1 this frame has no bottom padding: the content frame's own 32px top
     padding supplies the whole gap. The page background is white here (sampled from the
     Figma render), so no gray bodyClass tint. --}}
<div class="mx-auto flex max-w-[1440px] flex-col items-start gap-5 px-7 pt-8">
  <a class="shrink-0 whitespace-pre text-sm font-medium leading-[normal] text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step2.back') }}</a>
  <h1 class="shrink-0 whitespace-nowrap text-[32px] font-bold leading-[normal] tracking-[-0.5px] text-ink">{{ __('business-onboarding-step2.heading') }}</h1>
</div>

{{-- Content — Figma 1105:21048 (pad 32/28/48/28, gap 32). The Figma frame is a fixed
     1440 layout; below 1200 the two columns stack so the side column is never squeezed
     to a sliver. Nothing here changes the rendering at 1440px. --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pt-8 pb-12 max-[1200px]:flex-col">
  <div class="flex w-full max-w-[880px] flex-col items-start gap-2.5 max-[1200px]:max-w-none">

    {{-- Stepper — Figma 1105:21050 (880x68: pad 20 + 28 circle). Step 1 is done
         (yellow-line disc + tick), step 2 is current (ink disc + bold label), step 3 is
         upcoming: only its disc goes gray, the label stays ink at 13px. --}}
    <div class="flex w-full items-center justify-between border border-black/10 bg-white p-5 max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-3">
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow-line">
          <p class="whitespace-nowrap text-xs font-bold leading-[normal] text-ink" aria-hidden="true">✓</p>
        </div>
        <p class="whitespace-nowrap text-sm font-medium leading-[normal] text-ink">{{ __('business-onboarding-step2.steps.basic') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-ink">
          <p class="whitespace-nowrap text-[13px] font-bold leading-[normal] text-white">2</p>
        </div>
        <p class="whitespace-nowrap text-sm font-bold leading-[normal] text-ink" aria-current="step">{{ __('business-onboarding-step2.steps.contact') }}</p>
      </div>
      <div class="h-0.5 w-5 shrink-0 bg-[#e0e3e8]"></div>
      <div class="flex shrink-0 items-center gap-2">
        <div class="flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#e5e8ed]">
          <p class="whitespace-nowrap text-[13px] font-bold leading-[normal] text-[#808085]">3</p>
        </div>
        <p class="whitespace-nowrap text-[13px] font-medium leading-[normal] text-ink">{{ __('business-onboarding-step2.steps.product') }}</p>
      </div>
    </div>

    {{-- Form card — Figma 1105:21065 (pad 28/32, r4, stroke black@0.10, gap 20).
         Every control is <x-ui.field variant="b2b"> + <x-ui.input variant="b2b">; this
         frame's four deltas against .ui-control-b2b (square corners, black/10 stroke,
         12/14 padding, #808085 placeholder) ride on the control as utilities, which sit
         in a later layer than @layer components and therefore win. --}}
    <div class="flex w-full flex-col items-start gap-5 overflow-hidden rounded-ds border border-black/10 bg-white px-8 py-7">

      {{-- r1 — the only row with a 16px gutter (1105:21066) --}}
      <div class="flex w-full items-start gap-4 overflow-hidden bg-white max-[640px]:flex-col">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.contact_person_label')" for="onb2-contact-person"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-contact-person" name="contact_person" autocomplete="name"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.contact_person_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.role_label')" for="onb2-role"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-role" name="role" autocomplete="organization-title"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.role_placeholder')" />
        </x-ui.field>
      </div>

      {{-- frow — 14px gutter (1105:21075) --}}
      <div class="flex w-full items-start gap-3.5 max-[640px]:flex-col">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.phone_label')" for="onb2-phone"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-phone" type="tel" name="phone" autocomplete="tel"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.phone_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.whatsapp_label')" for="onb2-whatsapp"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-whatsapp" type="tel" name="whatsapp"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.whatsapp_placeholder')" />
        </x-ui.field>
      </div>

      {{-- frow (1105:21084) --}}
      <div class="flex w-full items-start gap-3.5 max-[640px]:flex-col">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.telegram_label')" for="onb2-telegram"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-telegram" name="telegram"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.telegram_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.email_label')" for="onb2-email"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-email" type="email" name="email" autocomplete="email"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.email_placeholder')" />
        </x-ui.field>
      </div>

      {{-- frow (1105:21093) --}}
      <div class="flex w-full items-start gap-3.5 max-[640px]:flex-col">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.website_label')" for="onb2-website"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-website" type="url" name="website" autocomplete="url"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.website_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.hours_label')" for="onb2-hours"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-hours" name="hours"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.hours_placeholder')" />
        </x-ui.field>
      </div>

      {{-- Social row — three equal columns (1105:21102) --}}
      <div class="flex w-full items-start gap-3.5 max-[640px]:flex-col">
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.instagram_label')" for="onb2-instagram"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-instagram" name="instagram"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.instagram_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.linkedin_label')" for="onb2-linkedin"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-linkedin" name="linkedin"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.linkedin_placeholder')" />
        </x-ui.field>
        <x-ui.field variant="b2b" :label="__('business-onboarding-step2.form.facebook_label')" for="onb2-facebook"
                    class="relative min-w-px flex-[1_0_0] items-start gap-1.5 max-[640px]:w-full">
          <x-ui.input variant="b2b" id="onb2-facebook" name="facebook"
                      class="rounded-none border-black/10 px-3.5 py-3 placeholder:text-[#808085]"
                      :placeholder="__('business-onboarding-step2.form.facebook_placeholder')" />
        </x-ui.field>
      </div>

      {{-- Communication languages — Figma 1105:21115. <x-ui.chip> supplies the tone and
           the reserved semibold width; the square corners, the 14px right padding, the
           black/10 idle stroke and the 1.5px selected stroke are this frame's geometry. --}}
      <div class="flex w-full flex-col items-start gap-2.5" role="group" aria-labelledby="onb2-languages-label">
        <p class="ui-label-b2b" id="onb2-languages-label">{{ __('business-onboarding-step2.form.languages_label') }}</p>
        <div class="flex w-full flex-wrap content-start items-start gap-2.5">
          @foreach (__('business-onboarding-step2.languages') as $key => $label)
            <x-ui.chip :label="$label" :on="in_array($key, ['az', 'ru', 'en'], true)" size="md"
                       data-language="{{ $key }}"
                       class="rounded-none pr-3.5 data-[on=false]:border-black/10 data-[on=true]:border-[1.5px]" />
          @endforeach
        </div>
      </div>

      {{-- Actions — Figma 1105:21136. They live INSIDE the card here; step 1 keeps its
           own separate bar. The label keeps its own <p>: the AZ string holds a double
           space before the arrow, which whitespace-pre must preserve.
           Funnel: step 1 (basic) -> step 2 (contact) -> step 3 (first product). --}}
      <div class="flex w-full items-center gap-3 overflow-hidden bg-white">
        <x-ui.button variant="primary" :hover="false" :href="route('business.onboarding.step3')" class="px-7 py-[15px]">
          <p class="shrink-0 whitespace-pre text-[15px] font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step2.actions.save') }}</p>
        </x-ui.button>
        <a class="shrink-0 whitespace-nowrap text-sm font-medium leading-[normal] text-black/50" href="{{ route('business.profile') }}">{{ __('business-onboarding-step2.actions.later') }}</a>
      </div>

    </div>
  </div>

  {{-- Side column — Figma 1105:21140 (472x215) --}}
  <div class="relative flex min-w-px flex-[1_0_0] flex-col items-start gap-4 overflow-hidden bg-white max-[1200px]:w-full max-[1200px]:flex-none">
    <div class="flex w-full shrink-0 flex-col items-start gap-3 overflow-hidden rounded-ds border border-black/10 bg-white p-5">
      <p class="shrink-0 whitespace-nowrap text-sm font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step2.side.progress_title') }}</p>
      {{-- Not <x-ui.progress>: two stacked fills with a 50% tail and rounded-ds, not the 3px cabinet bar. --}}
      <div class="relative h-1.5 w-full max-w-[280px] shrink-0 overflow-hidden rounded-ds bg-black/8">
        <div class="absolute top-0 left-0 h-1.5 w-[70px] rounded-ds bg-yellow"></div>
        <div class="absolute top-0 left-[70px] h-1.5 w-[70px] rounded-ds bg-yellow-line opacity-50"></div>
      </div>
      <p class="w-full shrink-0 text-[13px] font-normal leading-[normal] text-black/50">{{ __('business-onboarding-step2.side.progress_note') }}</p>
    </div>
    <div class="flex w-full shrink-0 flex-col items-start gap-2 overflow-hidden rounded-ds border border-yellow-line bg-sel-bg p-4 text-[13px]">
      <p class="shrink-0 whitespace-nowrap font-semibold leading-[normal] text-ink">{{ __('business-onboarding-step2.side.tip_title') }}</p>
      <p class="w-full shrink-0 font-normal leading-[1.5] text-black/70">{{ __('business-onboarding-step2.side.tip_text') }}</p>
    </div>
  </div>

</div>

</x-layout>
