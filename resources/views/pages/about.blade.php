@use('App\Models\Setting')
<x-layout page="about" :title="t('about.title')">

{{-- ===================== HERO ===================== --}}
<section class="about-hero relative flex items-end overflow-hidden" style="height:460px">
  <img class="absolute inset-0 size-full object-cover" src="{{ storage_url(Setting::get('about_hero_image'), '/assets/about-hero.jpg') }}" alt="">
  <div class="absolute inset-0 bg-black/[.58]"></div>
  <div class="wrap relative z-10 pb-14 max-[900px]:pb-10 max-[560px]:pb-8">
    <div class="inner flex flex-col gap-[18px]">
      <x-ui.breadcrumbs class="[&_a]:text-white/65 [&_.sep]:text-white/45 [&_.cur]:text-white/95" :items="[
          ['label' => t('common.home'), 'href' => route('home')],
          ['label' => t('about.crumb_current')],
      ]" />
      <x-ui.eyebrow variant="kicker" class="about-kicker-white" :label="t('about.crumb_current')" />
      <h1 class="max-w-[620px] text-[46px] leading-[1.18] font-bold text-white max-[900px]:text-[36px] max-[560px]:text-[28px]">{{ Setting::get('about_hero_title') }}</h1>
      <p class="max-w-[560px] text-[17px] leading-[1.55] text-white/70">{{ Setting::get('about_hero_subtitle') }}</p>
      <div class="flex gap-3 pt-2">
        <x-ui.button variant="primary" :href="Setting::get('about_cta1_url', '/catalog')"
                     class="h-12 rounded px-7 text-[15px] font-semibold">{{ Setting::get('about_cta1_text', t('about.hero.btn1')) }}</x-ui.button>
        <x-ui.button variant="outline" :href="Setting::get('about_cta2_url', '/specialists')"
                     class="h-12 rounded border-white/45 px-7 text-[15px] font-semibold text-white">{{ Setting::get('about_cta2_text', t('about.hero.btn2')) }}</x-ui.button>
      </div>
    </div>
  </div>
</section>

{{-- ===================== STATS ===================== --}}
<section class="bg-white">
  <div class="wrap py-10">
    <div class="inner">
      <div class="flex items-center max-[900px]:grid max-[900px]:grid-cols-2 max-[900px]:gap-6">
        @foreach ($stats as $i => $stat)
          @if ($i > 0)
            <div class="mx-auto h-12 w-px bg-black/10 max-[900px]:hidden"></div>
          @endif
          <div class="flex flex-1 flex-col gap-1.5 max-[900px]:flex-auto">
            <p class="text-[34px] leading-[normal] font-bold text-ink">{{ $stat->value }}</p>
            <p class="text-sm leading-[normal] text-black/50">{{ $stat->label }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ===================== MISSION ===================== --}}
<section class="wrap py-[72px] max-[560px]:py-14">
  <div class="inner">
    <div class="flex items-center gap-14 max-[1100px]:flex-col max-[1100px]:items-start max-[1100px]:gap-8">
      <div class="flex min-w-0 flex-1 flex-col gap-5 max-[1100px]:max-w-none" style="max-width:640px">
        <x-ui.eyebrow variant="kicker" :label="Setting::get('about_mission_tag', t('about.mission.tag'))" />
        <h2 class="text-[34px] leading-[1.18] font-bold text-ink max-[560px]:text-[26px]">{{ Setting::get('about_mission_title', t('about.mission.title')) }}</h2>
        <p class="text-[15px] leading-[1.65] text-black/50">{{ Setting::get('about_mission_p1', t('about.mission.p1')) }}</p>
        <p class="text-[15px] leading-[1.65] text-black/50">{{ Setting::get('about_mission_p2', t('about.mission.p2')) }}</p>
        <div class="flex flex-col gap-2.5 pt-1">
          @foreach ([
            Setting::get('about_mission_b1', t('about.mission.b1')),
            Setting::get('about_mission_b2', t('about.mission.b2')),
            Setting::get('about_mission_b3', t('about.mission.b3')),
          ] as $bullet)
            @if ($bullet)
            <div class="flex items-center gap-2.5">
              <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full bg-yellow">
                <svg class="size-3 text-ink" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <p class="text-[15px] leading-[normal] font-medium text-ink">{{ $bullet }}</p>
            </div>
            @endif
          @endforeach
        </div>
      </div>
      <div class="w-[688px] shrink-0 max-[1100px]:w-full">
        <img class="h-[440px] w-full rounded object-cover max-[1100px]:h-[320px]"
             src="{{ storage_url(Setting::get('about_mission_image'), '/assets/about-mission.jpg') }}" alt="">
      </div>
    </div>
  </div>
</section>

{{-- ===================== WHAT ===================== --}}
<section class="wrap py-[72px] max-[560px]:py-14">
  <div class="inner flex flex-col gap-10">
    <div class="sec-head-simple flex flex-col gap-3.5">
      <x-ui.eyebrow variant="kicker" :label="Setting::get('about_what_tag', t('about.what.tag'))" />
      <h2 class="text-[34px] leading-[normal] font-bold text-ink max-[560px]:text-[26px]">{{ Setting::get('about_what_title', t('about.what.title')) }}</h2>
    </div>

    <div class="grid grid-cols-3 gap-6 max-[900px]:grid-cols-1">
      @foreach ($features as $feat)
        <div class="about-feature flex flex-col gap-3.5 rounded border border-black/10 bg-white p-7">
          <span class="block h-[3px] w-10 rounded-sm bg-yellow"></span>
          <h3 class="text-[19px] leading-[normal] font-bold text-ink">{{ $feat['title'] }}</h3>
          <p class="text-sm leading-[1.6] text-black/50">{{ $feat['desc'] }}</p>
          <a href="{{ $feat['url'] }}" class="mt-auto inline-flex min-h-11 items-center self-start text-sm font-semibold text-ink hover:underline">{{ $feat['link'] }} →</a>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== HOW ===================== --}}
<section class="about-how-bg py-[72px] max-[560px]:py-14">
  <div class="wrap">
    <div class="inner flex flex-col gap-10">
      <div class="flex flex-col gap-3.5">
        <x-ui.eyebrow variant="kicker" :label="Setting::get('about_how_tag', t('about.how.tag'))" />
        <h2 class="text-[34px] leading-[normal] font-bold text-ink max-[560px]:text-[26px]">{{ Setting::get('about_how_title', t('about.how.title')) }}</h2>
      </div>

      <div class="flex items-start max-[900px]:grid max-[900px]:grid-cols-2 max-[900px]:gap-6 max-[560px]:grid-cols-1">
        @foreach ($steps as $i => $step)
          @if ($i > 0)
            <div class="mt-5 flex w-7 shrink-0 items-center max-[900px]:hidden">
              <span class="block h-0.5 w-full bg-black/15"></span>
            </div>
          @endif
          <div class="flex flex-1 flex-col gap-3.5">
            <span class="flex size-10 items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white">{{ $step->step_number }}</span>
            <h3 class="text-lg leading-[normal] font-bold text-ink">{{ $step->title }}</h3>
            <p class="text-sm leading-[1.6] text-black/50">{{ $step->description }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ===================== TEAM ===================== --}}
<section class="wrap py-[72px] max-[560px]:py-14">
  <div class="inner flex flex-col gap-9">
    <div class="flex flex-col gap-5">
      <x-ui.eyebrow variant="kicker" :label="Setting::get('about_team_tag', t('about.team.tag'))" />
      <h2 class="text-[34px] leading-[normal] font-bold text-ink max-[560px]:text-[26px]">{{ Setting::get('about_team_title', t('about.team.title')) }}</h2>
    </div>

    <div class="grid grid-cols-4 gap-6 max-[900px]:grid-cols-2 max-[560px]:grid-cols-2">
      @foreach ($team as $member)
        <div class="about-member flex flex-col items-start gap-4 rounded bg-[#f5f7f9] p-7">
          <span class="flex size-[88px] items-center justify-center rounded-full bg-white text-[26px] font-bold text-ink">{{ $member->initials }}</span>
          <div class="flex flex-col gap-1">
            <p class="text-[17px] leading-[normal] font-semibold text-black/90">{{ $member->name }}</p>
            <p class="text-sm leading-[normal] text-black/50">{{ $member->role }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== JOIN ===================== --}}
<section class="bg-ink py-[72px] max-[560px]:py-14">
  <div class="wrap">
    <div class="inner flex flex-col gap-10">
      <div class="flex flex-col gap-3.5">
        <x-ui.eyebrow variant="kicker" class="about-kicker-white" :label="Setting::get('about_join_tag', t('about.join.tag'))" />
        <h2 class="text-[34px] leading-[normal] font-bold text-white max-[560px]:text-[26px]">{{ Setting::get('about_join_title', t('about.join.title')) }}</h2>
      </div>

      <div class="grid grid-cols-2 gap-6 max-[900px]:grid-cols-1">
        @foreach ($joinCards as $card)
          <div class="flex flex-col gap-4 rounded border border-white/[.18] bg-white p-8">
            <h3 class="text-[22px] leading-[normal] font-bold text-ink">{{ $card['title'] }}</h3>
            <p class="text-[15px] leading-[1.6] text-black/50">{{ $card['desc'] }}</p>
            <div class="pt-1">
              <x-ui.button variant="primary" :href="$card['url']"
                           class="h-[46px] rounded px-6 text-[15px] font-semibold">{{ $card['btn'] }}</x-ui.button>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ===================== CONTACT ===================== --}}
<section class="wrap py-16 max-[560px]:py-14">
  <div class="inner flex flex-col gap-10">
    <div class="flex flex-col gap-3.5">
      <x-ui.eyebrow variant="kicker" :label="Setting::get('about_contact_tag', t('about.contact.tag'))" />
      <h2 class="text-[34px] leading-[normal] font-bold text-ink max-[560px]:text-[26px]">{{ Setting::get('about_contact_title', t('about.contact.title')) }}</h2>
    </div>

    <div class="grid grid-cols-3 gap-6 max-[900px]:grid-cols-1">
      <div class="flex flex-col gap-2">
        <p class="text-[13px] font-semibold tracking-wide text-black/50 uppercase">{{ t('about.contact.office_label') }}</p>
        <p class="text-[17px] font-semibold text-ink">{{ Setting::get('about_contact_address_line1') }}</p>
        <p class="text-sm text-black/50">{{ Setting::get('about_contact_address_line2') }}</p>
      </div>
      <div class="flex flex-col gap-2">
        <p class="text-[13px] font-semibold tracking-wide text-black/50 uppercase">{{ t('about.contact.phone_label') }}</p>
        {{-- self-start + min-h-11: a tel: link is the one thing on this page a phone
             visitor actually taps, so it needs a thumb-sized box (not a 26px line) --}}
        <a href="tel:{{ Setting::get('about_contact_phone') }}" class="inline-flex min-h-11 items-center self-start text-[17px] font-semibold text-ink hover:underline">{{ Setting::get('about_contact_phone') }}</a>
        <p class="text-sm text-black/50">{{ Setting::get('about_contact_hours') }}</p>
      </div>
      <div class="flex flex-col gap-2">
        <p class="text-[13px] font-semibold tracking-wide text-black/50 uppercase">{{ t('about.contact.email_label') }}</p>
        <a href="mailto:{{ Setting::get('about_contact_email') }}" class="inline-flex min-h-11 items-center self-start text-[17px] font-semibold text-ink hover:underline">{{ Setting::get('about_contact_email') }}</a>
        <p class="text-sm text-black/50">{{ Setting::get('about_contact_email_b2b') }}</p>
      </div>
    </div>
  </div>
</section>

</x-layout>
