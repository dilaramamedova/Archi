{{-- Specialist onboarding (Tamamlama) — Figma node 1054:9643 (1440x1560; navbar + banner + content).
     The navbar comes from the layout; the frame has no footer of its own. --}}
@php
    // The checklist rows. `done` renders the yellow ✓ pill + the green "Tamamlandı" label;
    // every other row gets the gray number pill and the yellow "Əlavə et" button.
    // Targets follow ARCHITECTURE §3.1: the checklist is the hub of the specialist cabinet.
    $steps = [
        ['key' => 'basics',    'n' => '✓', 'done' => true,  'href' => null],
        ['key' => 'specialty', 'n' => '2', 'done' => false, 'href' => route('specialist.cabinet')],
        ['key' => 'portfolio', 'n' => '3', 'done' => false, 'href' => route('specialist.cabinet.portfolio')],
        ['key' => 'schedule',  'n' => '4', 'done' => false, 'href' => route('specialist.cabinet.schedule')],
    ];
@endphp
<x-layout page="specialist-onboarding" :title="__('specialist-onboarding.title')">

{{-- Completion banner — Figma 1056:9735 (1440x72, pad 16/28, r0, sel-bg + yellow-line stroke).
     Full-bleed: the frame starts at x=0, so the tint runs edge to edge and only the inner
     row is clamped to 1440. --}}
<div class="w-full border-y border-yellow-line bg-sel-bg">
  <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-7 py-4 max-[640px]:flex-col max-[640px]:items-start">
    {{-- min-w-0 (not shrink-0): the RU string is longer than the AZ one and must wrap
         instead of pushing the CTA off the row. At 1440 nothing moves. --}}
    <div class="flex min-w-0 flex-col items-start gap-2">
      <p class="text-[15px] font-semibold leading-[normal] text-ink">{{ __('specialist-onboarding.banner.note') }}</p>
      {{-- 420x6 track, 40% (168px) yellow fill. Not <x-ui.progress>: that bar is 6px with a
           rounded-ds-sm fill and no track tint. --}}
      <div class="relative h-1.5 w-[420px] max-w-full shrink-0 overflow-hidden rounded-ds bg-black/8" role="progressbar"
           aria-valuemin="0" aria-valuemax="100" aria-valuenow="40" aria-label="{{ __('specialist-onboarding.banner.progress_label') }}">
        <div class="absolute top-0 left-0 h-1.5 w-[168px] max-w-full rounded-ds bg-yellow"></div>
      </div>
    </div>
    {{-- Stays on the page (§3.1): it scrolls the checklist into view and highlights the
         first unfinished row. --}}
    <x-ui.button variant="dark" class="px-5 py-[11px] text-sm font-semibold text-off-white" data-complete>{{ __('specialist-onboarding.banner.cta') }}</x-ui.button>
  </div>
</div>

{{-- Content — Figma 1056:9742 (pad 40/28/0/28, gap 32) --}}
<div class="mx-auto flex max-w-[1440px] items-start gap-8 px-7 pt-10 pb-10 max-[1200px]:flex-col">

  {{-- Checklist — Figma 1056:9743 (880 wide, r4, stroke black@0.10) --}}
  <div class="onb-checklist flex w-full max-w-[880px] flex-col items-start overflow-hidden rounded-ds border border-black/10 bg-white max-[1200px]:max-w-none">
    <div class="flex w-full shrink-0 flex-col items-start gap-1.5 px-6 pt-[22px] pb-[18px]">
      <h1 class="shrink-0 text-xl font-bold leading-[normal] text-ink">{{ __('specialist-onboarding.checklist.title') }}</h1>
      <p class="shrink-0 text-[13px] font-normal leading-[normal] text-black/50">{{ __('specialist-onboarding.checklist.desc') }}</p>
    </div>

    @foreach ($steps as $step)
      {{-- Step row — Figma 1056:9747 (74 tall: pad 18/24, gap 16, top rule black@0.10) --}}
      <div class="flex w-full shrink-0 items-center gap-4 border-t border-black/10 px-6 py-[18px] data-[cue=true]:bg-sel-bg max-[640px]:flex-wrap"
           data-step data-done="{{ $step['done'] ? 'true' : 'false' }}" data-cue="false">
        <div @class([
            'flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-pill',
            'bg-yellow' => $step['done'],
            'bg-gray-soft' => ! $step['done'],
        ])>
          <p @class([
              'shrink-0 whitespace-nowrap text-sm font-semibold leading-[normal]',
              'text-ink' => $step['done'],
              'text-black/50' => ! $step['done'],
          ])>{{ $step['n'] }}</p>
        </div>
        <div class="flex min-w-px flex-[1_0_0] flex-col items-start gap-1 overflow-hidden">
          <p class="shrink-0 text-[15px] font-semibold leading-[normal] text-ink">{{ __('specialist-onboarding.steps.' . $step['key'] . '.title') }}</p>
          <p class="shrink-0 text-[13px] font-normal leading-[normal] text-black/50">{{ __('specialist-onboarding.steps.' . $step['key'] . '.desc') }}</p>
        </div>
        @if ($step['done'])
          <p class="shrink-0 whitespace-nowrap text-[13px] font-medium leading-[normal] text-green">{{ __('specialist-onboarding.steps.basics.status') }}</p>
        @else
          <x-ui.button variant="primary" :href="$step['href']" class="px-4 py-[9px] text-[13px] font-semibold">{{ __('specialist-onboarding.steps.add') }}</x-ui.button>
        @endif
      </div>
    @endforeach
  </div>

  {{-- Preview — Figma 1056:14346 (472 wide, gap 16). The card is the shared <x-scard> at
       45% opacity: the profile is not live yet. --}}
  <div class="flex min-w-px flex-[1_0_0] flex-col items-start gap-4 max-[1200px]:w-full max-[1200px]:flex-none">
    <p class="shrink-0 text-[15px] font-semibold leading-[normal] text-ink">{{ __('specialist-onboarding.preview.title') }}</p>
    <x-scard
        class="w-[340px] max-w-full flex-none opacity-45"
        aria-hidden="true"
        :role="__('specialist-onboarding.preview.card.role')"
        rate="4.9"
        :reviews="__('specialist-onboarding.preview.card.reviews')"
        :name="__('specialist-onboarding.preview.card.name')"
        :exp="__('specialist-onboarding.preview.card.exp')"
        :proj="__('specialist-onboarding.preview.card.proj')" />
    {{-- Lock note — Figma 1056:14378 (40 tall: pad 12/14, gap 10, gray-soft2, r4) --}}
    <div class="flex w-full shrink-0 items-center gap-2.5 overflow-hidden rounded-ds bg-gray-soft2 px-3.5 py-3">
      <p class="shrink-0 whitespace-nowrap text-sm font-normal leading-[normal] text-black" aria-hidden="true">🔒</p>
      <p class="min-w-px flex-[1_0_0] text-[13px] font-normal leading-[normal] text-black/70">{{ __('specialist-onboarding.preview.lock') }}</p>
    </div>
  </div>

</div>

</x-layout>
