@use('App\Models\Setting')
{{--
  About page (Haqqimizda). Built from Figma node 831:7539 — there is no old static page
  to port. Four sections: hero + stat tiles, the story split, the values grid and the
  dark CTA band. Everything reuses the design system (<x-ui.breadcrumbs>, <x-ui.eyebrow>,
  <x-ui.button>) plus @theme tokens; only the four value icons and the story photo are
  page assets. The "team" frame of the node is hidden in Figma, so it is not built.
--}}
<x-layout page="about" :title="Setting::get('about_hero_tag', __('about.title'))">

<div class="wrap pt-10 pb-[72px]">
  <div class="inner flex flex-col gap-14">

    {{-- ===================== HERO ===================== --}}
    <section class="flex flex-col gap-5">
      <x-ui.breadcrumbs :items="[
          ['label' => __('common.home'), 'href' => route('home')],
          ['label' => __('about.crumb_current')],
      ]" />

      <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="Setting::get('about_hero_tag')" />

      <h1 class="text-[52px] leading-[normal] font-bold text-ink max-[900px]:text-[38px] max-[560px]:text-[30px]">{{ Setting::get('about_hero_title') }}</h1>

      <p class="w-[760px] max-w-full text-lg leading-[1.55] text-black/55">{{ Setting::get('about_hero_subtitle') }}</p>

      {{-- stat tiles --}}
      <div class="grid grid-cols-4 gap-3 pt-2 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
        @foreach ($stats as $stat)
          <div class="about-stat flex flex-col gap-1 overflow-hidden rounded-ds bg-gray-soft2 px-6 py-[22px]">
            <p class="text-[34px] leading-[normal] font-bold text-ink">{{ $stat->value }}</p>
            <p class="text-sm leading-[normal] font-medium text-black/55">{{ $stat->label }}</p>
          </div>
        @endforeach
      </div>
    </section>

    {{-- ===================== STORY ===================== --}}
    <section class="about-story flex items-center gap-14 max-[1100px]:flex-col max-[1100px]:items-start max-[1100px]:gap-8">
      <img class="h-[420px] w-[620px] shrink-0 rounded-ds object-cover max-[1100px]:h-[360px] max-[1100px]:w-full"
           src="{{ Setting::get('about_story_image', '/assets/renovation-before-after.png') }}" alt="{{ Setting::get('about_story_image_alt') }}">

      <div class="flex min-w-0 flex-1 flex-col gap-4">
        <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="Setting::get('about_story_tag')" />
        <h2 class="text-4xl leading-[normal] font-semibold text-black max-[560px]:text-[28px]">{{ Setting::get('about_story_title') }}</h2>
        <p class="text-[15px] leading-[1.6] text-black/65">{{ Setting::get('about_story_paragraph_1') }}</p>
        <p class="text-[15px] leading-[1.6] text-black/65">{{ Setting::get('about_story_paragraph_2') }}</p>

        <div class="flex items-center gap-3 pt-1.5">
          <span class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-yellow bg-ink text-sm font-bold text-white">{{ Setting::get('about_story_author_initials') }}</span>
          <div class="flex flex-col gap-0.5 leading-[normal]">
            <p class="text-sm font-semibold text-ink">{{ Setting::get('about_story_author_name') }}</p>
            <p class="text-xs text-black/50">{{ Setting::get('about_story_author_role') }}</p>
          </div>
        </div>
      </div>
    </section>

    {{-- ===================== VALUES ===================== --}}
    <section class="flex flex-col gap-5">
      <div class="about-values-head flex flex-col gap-5">
        <x-ui.eyebrow variant="kicker" class="about-eyebrow" :label="Setting::get('about_values_tag')" />
        <h2 class="text-4xl leading-[normal] font-semibold text-black max-[560px]:text-[28px]">{{ Setting::get('about_values_title') }}</h2>
      </div>

      <div class="grid grid-cols-4 items-start gap-3 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
        @foreach ($values as $value)
          <x-ui.card class="about-value flex flex-col gap-3 overflow-hidden px-[22px] py-6">
            <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-yellow">
              <img class="size-6" src="{{ storage_url($value->icon) }}" alt="">
            </span>
            <h3 class="text-lg leading-[normal] font-bold text-ink">{{ $value->title }}</h3>
            <p class="text-[13px] leading-[1.55] text-black/60">{{ $value->description }}</p>
          </x-ui.card>
        @endforeach
      </div>
    </section>

    {{-- ===================== CTA BAND ===================== --}}
    <section class="about-cta flex items-center justify-between gap-8 overflow-hidden rounded-ds bg-ink p-11 max-[900px]:flex-col max-[900px]:items-start max-[560px]:p-7">
      <div class="flex flex-col gap-2 leading-[normal]">
        <h2 class="text-[28px] font-bold text-white max-[560px]:text-[22px]">{{ Setting::get('about_cta_title') }}</h2>
        <p class="text-[15px] text-white/65">{{ Setting::get('about_cta_subtitle') }}</p>
      </div>

      <div class="flex shrink-0 gap-3 max-[560px]:w-full max-[560px]:flex-col">
        <x-ui.button variant="primary" :href="Setting::get('about_cta_button1_url', '/register')"
                     class="h-[52px] px-7 text-[15px] leading-[normal] font-bold whitespace-nowrap">{{ Setting::get('about_cta_button1_text') }}</x-ui.button>
        <x-ui.button variant="on-ink" :href="Setting::get('about_cta_button2_url', '/sell')"
                     class="h-[52px] border-white/40 px-7 text-[15px] leading-[normal] font-semibold whitespace-nowrap text-white">{{ Setting::get('about_cta_button2_text') }}</x-ui.button>
      </div>
    </section>

  </div>
</div>

</x-layout>
