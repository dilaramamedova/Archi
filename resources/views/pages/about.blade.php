{{--
  About page (Haqqimizda). Built from Figma node 831:7539 — there is no old static page
  to port. Four sections: hero + stat tiles, the story split, the values grid and the
  dark CTA band. Everything reuses the design system (<x-ui.breadcrumbs>, <x-ui.eyebrow>,
  <x-ui.button>) plus @theme tokens; only the four value icons and the story photo are
  page assets. The "team" frame of the node is hidden in Figma, so it is not built.
--}}
@php
    $stats = ['catalog', 'masters', 'orders', 'since'];

    $values = [
        'trust'        => '/assets/icon-shield-check.svg',
        'quality'      => '/assets/icon-star-outline.svg',
        'transparency' => '/assets/icon-eye.svg',
        'support'      => '/assets/icon-speech-bubble.svg',
    ];
@endphp
<x-layout page="about" :title="__('about.title')">

<div class="wrap pt-10 pb-[72px]">
  <div class="inner flex flex-col gap-14">

    {{-- ===================== HERO ===================== --}}
    <section class="flex flex-col gap-5">
      <x-ui.breadcrumbs :items="[
          ['label' => __('common.home'), 'href' => route('home')],
          ['label' => __('about.crumb_current')],
      ]" />

      <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="__('about.hero.tag')" />

      <h1 class="text-[52px] leading-[normal] font-bold text-ink max-[900px]:text-[38px] max-[560px]:text-[30px]">{{ __('about.hero.title') }}</h1>

      <p class="w-[760px] max-w-full text-lg leading-[1.55] text-black/55">{{ __('about.hero.subtitle') }}</p>

      {{-- stat tiles --}}
      <div class="grid grid-cols-4 gap-3 pt-2 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
        @foreach ($stats as $stat)
          <div class="about-stat flex flex-col gap-1 overflow-hidden rounded-ds bg-gray-soft2 px-6 py-[22px]">
            <p class="text-[34px] leading-[normal] font-bold text-ink">{{ __('about.stats.' . $stat . '_value') }}</p>
            <p class="text-sm leading-[normal] font-medium text-black/55">{{ __('about.stats.' . $stat . '_label') }}</p>
          </div>
        @endforeach
      </div>
    </section>

    {{-- ===================== STORY ===================== --}}
    <section class="about-story flex items-center gap-14 max-[1100px]:flex-col max-[1100px]:items-start max-[1100px]:gap-8">
      <img class="h-[420px] w-[620px] shrink-0 rounded-ds object-cover max-[1100px]:h-[360px] max-[1100px]:w-full"
           src="/assets/renovation-before-after.png" alt="{{ __('about.story.image_alt') }}">

      <div class="flex min-w-0 flex-1 flex-col gap-4">
        <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="__('about.story.tag')" />
        <h2 class="text-4xl leading-[normal] font-semibold text-black max-[560px]:text-[28px]">{{ __('about.story.title') }}</h2>
        <p class="text-[15px] leading-[1.6] text-black/65">{{ __('about.story.paragraph_1') }}</p>
        <p class="text-[15px] leading-[1.6] text-black/65">{{ __('about.story.paragraph_2') }}</p>

        <div class="flex items-center gap-3 pt-1.5">
          <span class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-yellow bg-ink text-sm font-bold text-white">{{ __('about.story.author_initials') }}</span>
          <div class="flex flex-col gap-0.5 leading-[normal]">
            <p class="text-sm font-semibold text-ink">{{ __('about.story.author_name') }}</p>
            <p class="text-xs text-black/50">{{ __('about.story.author_role') }}</p>
          </div>
        </div>
      </div>
    </section>

    {{-- ===================== VALUES ===================== --}}
    <section class="flex flex-col gap-5">
      <div class="about-values-head flex flex-col gap-5">
        <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="__('about.values.tag')" />
        <h2 class="text-4xl leading-[normal] font-semibold text-black max-[560px]:text-[28px]">{{ __('about.values.title') }}</h2>
      </div>

      <div class="grid grid-cols-4 items-start gap-3 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
        @foreach ($values as $value => $icon)
          <x-ui.card class="about-value flex flex-col gap-3 overflow-hidden px-[22px] py-6">
            <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow">
              <img class="size-6" src="{{ $icon }}" alt="">
            </span>
            <h3 class="text-lg leading-[normal] font-bold text-ink">{{ __('about.values.' . $value . '_title') }}</h3>
            <p class="text-[13px] leading-[1.55] text-black/60">{{ __('about.values.' . $value . '_text') }}</p>
          </x-ui.card>
        @endforeach
      </div>
    </section>

    {{-- ===================== CTA BAND ===================== --}}
    <section class="about-cta flex items-center justify-between gap-8 overflow-hidden rounded-ds bg-ink p-11 max-[900px]:flex-col max-[900px]:items-start max-[560px]:p-7">
      <div class="flex flex-col gap-2 leading-[normal]">
        <h2 class="text-[28px] font-bold text-white max-[560px]:text-[22px]">{{ __('about.cta.title') }}</h2>
        <p class="text-[15px] text-white/65">{{ __('about.cta.subtitle') }}</p>
      </div>

      <div class="flex shrink-0 gap-3 max-[560px]:w-full max-[560px]:flex-col">
        <x-ui.button variant="primary" :href="route('register')"
                     class="h-[52px] px-7 text-[15px] leading-[normal] font-bold whitespace-nowrap">{{ __('about.cta.become_master') }}</x-ui.button>
        <x-ui.button variant="on-ink" :href="route('sell')"
                     class="h-[52px] border-white/40 px-7 text-[15px] leading-[normal] font-semibold whitespace-nowrap text-white">{{ __('about.cta.become_seller') }}</x-ui.button>
      </div>
    </section>

  </div>
</div>

</x-layout>
