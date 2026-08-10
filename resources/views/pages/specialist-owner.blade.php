{{--
  Specialist profile in owner mode (Figma 831:11493, `spo-` prefix). It is the public
  `specialist` page seen by its own owner: a full-bleed yellow banner and a stats strip
  sit above the profile, and every section carries a dashed "edit" chip that links to the
  matching specialist-cabinet tab (ARCHITECTURE.md §3.1).
--}}
@php
    $stats = [
        ['key' => 'views', 'delta' => true],
        ['key' => 'requests', 'delta' => true],
        ['key' => 'response', 'delta' => false],
        ['key' => 'rating', 'delta' => false],
    ];
    $starIcon = '/assets/icon-star-black.svg';
    $pencil = '<svg class="spo-pen" viewBox="0 0 12 8" fill="none" aria-hidden="true" focusable="false">'
        . '<path d="M1 6.8 3.073 6.61 11.017 2.141 10.183.659 2.239 5.128Z" stroke="currentColor" stroke-width=".8" stroke-linejoin="round"/>'
        . '<path d="m8.789 1.443.834 1.482" stroke="currentColor" stroke-width=".8" stroke-linecap="round"/>'
        . '</svg>';

    $initials = mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1);
    $portfolioItems = $profile?->portfolioItems ?? collect();
    $services = $profile?->services ?? collect();
@endphp
<x-layout page="specialist-owner" :title="t('specialist-owner.title')">

{{-- owner banner (831:11530) — full-bleed yellow strip, 64px --}}
<div class="spo-banner">
  <div class="in">
    <div class="spo-note">
      <span class="ic" aria-hidden="true">&#128065;</span>
      <p>{{ t('specialist-owner.banner.notice') }}</p>
    </div>
    <div class="spo-banner-r">
      <div class="spo-avail">
        <span class="l">{{ t('specialist-owner.banner.available') }}</span>
        <x-ui.toggle size="sm" :on="!($profile?->is_on_vacation)" data-sel="availability"
            :aria-label="t('specialist-owner.banner.available')" />
      </div>
      <x-ui.button variant="dark" :href="route('specialist.cabinet')"
          class="h-10 gap-1.5 px-[18px] text-[13px] leading-[normal] font-bold text-yellow">
        {!! $pencil !!}{{ t('specialist-owner.banner.edit') }}
      </x-ui.button>
    </div>
  </div>
</div>

{{-- stats strip (831:11542) --}}
<div class="spo-statsbar">
  <div class="in">
    @foreach ($stats as $stat)
      <div class="spo-stat">
        <div class="b">
          <div class="v">
            <span class="n">{{ t('specialist-owner.stats.' . $stat['key'] . '.value') }}</span>
            @if ($stat['delta'])
              <span class="d">{{ t('specialist-owner.stats.' . $stat['key'] . '.delta') }}</span>
            @endif
          </div>
          <p class="k">{{ t('specialist-owner.stats.' . $stat['key'] . '.label') }}</p>
        </div>
      </div>
    @endforeach
  </div>
</div>

<main class="spo">

  {{-- breadcrumb --}}
  <x-ui.breadcrumbs class="spo-crumbs" :items="[
      ['label' => t('common.home'), 'href' => route('home')],
      ['label' => t('specialist-owner.crumb_specialists'), 'href' => route('specialists')],
      ['label' => $user->name],
  ]" />

  {{-- header --}}
  <div class="spo-head">
    <div class="spo-head-l">

      <div class="spo-id">
        @if($profile?->avatar_path)
          <img class="spo-ava" src="{{ storage_url($profile->avatar_path) }}" alt="{{ $user->name }}">
        @else
          <div class="spo-ava" aria-hidden="true">{{ $initials ?: t('specialist-owner.id.initials_fallback') }}</div>
        @endif
        <div class="spo-idt">
          <div class="spo-badges">
            @if($profile?->is_featured)
              <span class="spo-badge">{{ t('specialist-owner.id.badge_top') }}</span>
            @endif
            <span class="spo-badge ok">{{ t('specialist-owner.id.badge_verified') }}</span>
          </div>
          <h1 class="spo-name">{{ $user->name }}</h1>
          <p class="spo-role">{{ $profile?->craft_label ?: t('specialist-owner.id.role') }}</p>
          <div class="spo-rate">
            <span class="v"><x-ui.stars :count="1" :rating="1" :icon="$starIcon" />{{ t('specialist-owner.id.rate') }}</span>
            <span class="r">{{ t('specialist-owner.id.reviews') }}</span>
          </div>
        </div>
      </div>

      <p class="spo-meta">
        {{ $profile?->experience_years ?? 0 }} {{ t('specialist-owner.id.years') }}
        <span class="dot">·</span>
        {{ $portfolioItems->count() }} {{ t('specialist-owner.id.projects') }}
        <span class="dot">·</span>
        {{ $profile?->city ?? t('specialist-owner.id.meta_city') }}
      </p>

    </div>

    {{-- booking card --}}
    <aside class="spo-book">
      <div class="ph">
        <span class="lbl">{{ t('specialist-owner.book.label') }}</span>
        <div class="prow"><span class="now">{{ t('specialist-owner.book.price') }}</span><span class="sub">{{ t('specialist-owner.book.price_sub') }}</span></div>
      </div>
      <x-ui.button variant="primary" class="w-full rounded-none px-6 py-4 text-base leading-[normal] font-medium duration-200">{{ t('specialist-owner.book.consult') }}</x-ui.button>
      <div class="div"></div>
      <div class="spo-stat-row"><span class="k">{{ t('specialist-owner.book.response_k') }}</span><span class="v">{{ t('specialist-owner.book.response_v') }}</span></div>
      <div class="spo-stat-row"><span class="k">{{ t('specialist-owner.book.done_k') }}</span><span class="v">{{ $portfolioItems->count() }}</span></div>
      <div class="spo-stat-row"><span class="k">{{ t('specialist-owner.book.member_k') }}</span><span class="v">{{ $profile?->created_at?->format('M Y') ?? '-' }}</span></div>
    </aside>
  </div>

  @if (filled($profile?->about) || count($profile?->skills ?? []))
  {{-- about --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="t('specialist-owner.about.eyebrow')" />
      <h2>{{ t('specialist-owner.about.title') }}</h2>
    </div>
    @if (filled($profile?->about))
      <p class="spo-about-txt">{{ $profile->about }}</p>
    @endif
    @if (count($profile?->skills ?? []))
      <div class="spo-tags">
      @foreach ($profile->skills as $skill)
        <span class="spo-tag">{{ $skill }}</span>
      @endforeach
      </div>
    @endif
    <a class="spo-edit" href="{{ route('specialist.cabinet') }}">{!! $pencil !!}{{ t('specialist-owner.edit') }}</a>
  </section>
  @endif

  @if ($portfolioItems->isNotEmpty())
  {{-- portfolio --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="t('specialist-owner.portfolio.eyebrow')" />
      <h2>{{ t('specialist-owner.portfolio.title') }}</h2>
    </div>
    <div class="spo-grid">
      @if($portfolioItems->isNotEmpty())
        @foreach (array_chunk($portfolioItems->all(), 3) as $row)
          <div class="spo-grow">
            @foreach ($row as $item)
              <a class="spo-tile" href="{{ route('specialist.cabinet.portfolio') }}"><img src="{{ storage_url($item->image_path) }}" alt="{{ $item->title ?? '' }}"></a>
            @endforeach
          </div>
        @endforeach
      @else
        @php
          // TODO: Replace with dynamic data from controller — fallback portfolio images should be configurable.
          $fallbackTiles = ['portfolio-stone-tile-samples.jpg', 'portfolio-marble-tile-dark.jpg', 'portfolio-renovation-before-after.jpg', 'portfolio-roof-tile-showroom.jpg', 'portfolio-electrical-showroom.jpg', 'portfolio-laminate-flooring.jpg'];
        @endphp
        @foreach (array_chunk($fallbackTiles, 3) as $row)
          <div class="spo-grow">
            @foreach ($row as $img)
              <a class="spo-tile" href="{{ route('specialist.cabinet.portfolio') }}"><img src="/assets/{{ $img }}" alt=""></a>
            @endforeach
          </div>
        @endforeach
      @endif
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.portfolio') }}">{!! $pencil !!}{{ t('specialist-owner.edit') }}</a>
  </section>
  @endif

  @if ($services->isNotEmpty())
  {{-- services --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="t('specialist-owner.services.eyebrow')" />
      <h2>{{ t('specialist-owner.services.title') }}</h2>
    </div>
    <div class="spo-svc-list">
      @forelse ($services as $svc)
        <a class="spo-svc" href="{{ route('specialist.cabinet.services') }}">
          <span class="l"><span class="t">{{ $svc->name }}</span><span class="s">{{ $svc->description ?? '' }}</span></span>
          <span class="rr"><span class="pr">{{ $svc->price ? number_format($svc->price, 0) . ' ₼' : '' }}</span><span class="ar" aria-hidden="true">&rarr;</span></span>
        </a>
      @empty
        @foreach (['s1', 's2', 's3', 's4'] as $key)
          <a class="spo-svc" href="{{ route('specialist.cabinet.services') }}">
            <span class="l"><span class="t">{{ t('specialist-owner.services.items.' . $key . '.title') }}</span><span class="s">{{ t('specialist-owner.services.items.' . $key . '.sub') }}</span></span>
            <span class="rr"><span class="pr">{{ t('specialist-owner.services.items.' . $key . '.price') }}</span><span class="ar" aria-hidden="true">&rarr;</span></span>
          </a>
        @endforeach
      @endforelse
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.services') }}">{!! $pencil !!}{{ t('specialist-owner.edit') }}</a>
  </section>
  @endif

  {{-- reviews --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="t('specialist-owner.reviews.eyebrow')" />
      <h2>{{ t('specialist-owner.reviews.title') }}</h2>
    </div>
    <div class="spo-summ">
      <span class="n">{{ t('specialist-owner.reviews.score') }}</span>
      <span class="c">
        <span class="ss"><x-ui.stars :rating="5" :icon="$starIcon" /></span>
        <span class="cnt">{{ t('specialist-owner.reviews.count') }}</span>
      </span>
    </div>
    <div class="spo-revs">
      @foreach (['r1', 'r2'] as $rev)
        <article class="spo-rev">
          <div class="hd">
            <span class="av" aria-hidden="true">{{ t('specialist-owner.reviews.items.' . $rev . '.initial') }}</span>
            <span class="nm"><span class="n">{{ t('specialist-owner.reviews.items.' . $rev . '.name') }}</span><span class="d">{{ t('specialist-owner.reviews.items.' . $rev . '.date') }}</span></span>
          </div>
          <div class="st"><x-ui.stars :rating="5" :icon="$starIcon" /></div>
          <p class="tx">{{ t('specialist-owner.reviews.items.' . $rev . '.text') }}</p>
        </article>
      @endforeach
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.reviews') }}">{!! $pencil !!}{{ t('specialist-owner.edit') }}</a>
  </section>

</main>

</x-layout>
