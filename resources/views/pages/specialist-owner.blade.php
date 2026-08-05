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
<x-layout page="specialist-owner" :title="__('specialist-owner.title')">

{{-- owner banner (831:11530) — full-bleed yellow strip, 64px --}}
<div class="spo-banner">
  <div class="in">
    <div class="spo-note">
      <span class="ic" aria-hidden="true">&#128065;</span>
      <p>{{ __('specialist-owner.banner.notice') }}</p>
    </div>
    <div class="spo-banner-r">
      <div class="spo-avail">
        <span class="l">{{ __('specialist-owner.banner.available') }}</span>
        <x-ui.toggle size="sm" :on="!($profile?->is_on_vacation)" data-sel="availability"
            :aria-label="__('specialist-owner.banner.available')" />
      </div>
      <x-ui.button variant="dark" :href="route('specialist.cabinet')"
          class="h-10 gap-1.5 px-[18px] text-[13px] leading-[normal] font-bold text-yellow">
        {!! $pencil !!}{{ __('specialist-owner.banner.edit') }}
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
            <span class="n">{{ __('specialist-owner.stats.' . $stat['key'] . '.value') }}</span>
            @if ($stat['delta'])
              <span class="d">{{ __('specialist-owner.stats.' . $stat['key'] . '.delta') }}</span>
            @endif
          </div>
          <p class="k">{{ __('specialist-owner.stats.' . $stat['key'] . '.label') }}</p>
        </div>
      </div>
    @endforeach
  </div>
</div>

<main class="spo">

  {{-- breadcrumb --}}
  <x-ui.breadcrumbs class="spo-crumbs" :items="[
      ['label' => __('common.home'), 'href' => route('home')],
      ['label' => __('specialist-owner.crumb_specialists'), 'href' => route('specialists')],
      ['label' => $user->name],
  ]" />

  {{-- header --}}
  <div class="spo-head">
    <div class="spo-head-l">

      <div class="spo-id">
        @if($profile?->avatar_path)
          <img class="spo-ava" src="{{ storage_url($profile->avatar_path) }}" alt="{{ $user->name }}">
        @else
          <div class="spo-ava" aria-hidden="true">{{ $initials ?: __('specialist-owner.id.initials_fallback') }}</div>
        @endif
        <div class="spo-idt">
          <div class="spo-badges">
            @if($profile?->is_featured)
              <span class="spo-badge">{{ __('specialist-owner.id.badge_top') }}</span>
            @endif
            <span class="spo-badge ok">{{ __('specialist-owner.id.badge_verified') }}</span>
          </div>
          <h1 class="spo-name">{{ $user->name }}</h1>
          <p class="spo-role">{{ translate_craft($profile?->craft) ?? __('specialist-owner.id.role') }}</p>
          <div class="spo-rate">
            <span class="v"><x-ui.stars :count="1" :rating="1" :icon="$starIcon" />{{ __('specialist-owner.id.rate') }}</span>
            <span class="r">{{ __('specialist-owner.id.reviews') }}</span>
          </div>
        </div>
      </div>

      <p class="spo-meta">
        {{ $profile?->experience_years ?? 0 }} {{ __('specialist-owner.id.years') }}
        <span class="dot">·</span>
        {{ $portfolioItems->count() }} {{ __('specialist-owner.id.projects') }}
        <span class="dot">·</span>
        {{ $profile?->city ?? __('specialist-owner.id.meta_city') }}
      </p>

    </div>

    {{-- booking card --}}
    <aside class="spo-book">
      <div class="ph">
        <span class="lbl">{{ __('specialist-owner.book.label') }}</span>
        <div class="prow"><span class="now">{{ __('specialist-owner.book.price') }}</span><span class="sub">{{ __('specialist-owner.book.price_sub') }}</span></div>
      </div>
      <x-ui.button variant="primary" class="w-full rounded-none px-6 py-4 text-base leading-[normal] font-medium duration-200">{{ __('specialist-owner.book.consult') }}</x-ui.button>
      <button type="button" class="spo-btn w">{{ __('specialist-owner.book.message') }}</button>
      <div class="div"></div>
      <div class="spo-stat-row"><span class="k">{{ __('specialist-owner.book.response_k') }}</span><span class="v">{{ __('specialist-owner.book.response_v') }}</span></div>
      <div class="spo-stat-row"><span class="k">{{ __('specialist-owner.book.done_k') }}</span><span class="v">{{ $portfolioItems->count() }}</span></div>
      <div class="spo-stat-row"><span class="k">{{ __('specialist-owner.book.member_k') }}</span><span class="v">{{ $profile?->created_at?->format('M Y') ?? '-' }}</span></div>
    </aside>
  </div>

  {{-- about --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="__('specialist-owner.about.eyebrow')" />
      <h2>{{ __('specialist-owner.about.title') }}</h2>
    </div>
    <p class="spo-about-txt">{{ $profile?->about ?? __('specialist-owner.about.text') }}</p>
    <div class="spo-tags">
      @forelse ($profile?->skills ?? [] as $skill)
        <span class="spo-tag">{{ $skill }}</span>
      @empty
        @foreach (['t1', 't2', 't3', 't4', 't5', 't6'] as $tag)
          <span class="spo-tag">{{ __('specialist-owner.about.tags.' . $tag) }}</span>
        @endforeach
      @endforelse
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet') }}">{!! $pencil !!}{{ __('specialist-owner.edit') }}</a>
  </section>

  {{-- portfolio --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="__('specialist-owner.portfolio.eyebrow')" />
      <h2>{{ __('specialist-owner.portfolio.title') }}</h2>
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
    <a class="spo-edit" href="{{ route('specialist.cabinet.portfolio') }}">{!! $pencil !!}{{ __('specialist-owner.edit') }}</a>
  </section>

  {{-- services --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="__('specialist-owner.services.eyebrow')" />
      <h2>{{ __('specialist-owner.services.title') }}</h2>
    </div>
    <div class="spo-svc-list">
      @forelse ($services as $svc)
        <a class="spo-svc" href="{{ route('specialist.cabinet.services') }}">
          <span class="l"><span class="t">{{ $svc->title }}</span><span class="s">{{ $svc->description ?? '' }}</span></span>
          <span class="rr"><span class="pr">{{ $svc->price ? number_format($svc->price, 0) . ' ₼' : '' }}</span><span class="ar" aria-hidden="true">&rarr;</span></span>
        </a>
      @empty
        @foreach (['s1', 's2', 's3', 's4'] as $key)
          <a class="spo-svc" href="{{ route('specialist.cabinet.services') }}">
            <span class="l"><span class="t">{{ __('specialist-owner.services.items.' . $key . '.title') }}</span><span class="s">{{ __('specialist-owner.services.items.' . $key . '.sub') }}</span></span>
            <span class="rr"><span class="pr">{{ __('specialist-owner.services.items.' . $key . '.price') }}</span><span class="ar" aria-hidden="true">&rarr;</span></span>
          </a>
        @endforeach
      @endforelse
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.services') }}">{!! $pencil !!}{{ __('specialist-owner.edit') }}</a>
  </section>

  {{-- reviews --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="__('specialist-owner.reviews.eyebrow')" />
      <h2>{{ __('specialist-owner.reviews.title') }}</h2>
    </div>
    <div class="spo-summ">
      <span class="n">{{ __('specialist-owner.reviews.score') }}</span>
      <span class="c">
        <span class="ss"><x-ui.stars :rating="5" :icon="$starIcon" /></span>
        <span class="cnt">{{ __('specialist-owner.reviews.count') }}</span>
      </span>
    </div>
    <div class="spo-revs">
      @foreach (['r1', 'r2'] as $rev)
        <article class="spo-rev">
          <div class="hd">
            <span class="av" aria-hidden="true">{{ __('specialist-owner.reviews.items.' . $rev . '.initial') }}</span>
            <span class="nm"><span class="n">{{ __('specialist-owner.reviews.items.' . $rev . '.name') }}</span><span class="d">{{ __('specialist-owner.reviews.items.' . $rev . '.date') }}</span></span>
          </div>
          <div class="st"><x-ui.stars :rating="5" :icon="$starIcon" /></div>
          <p class="tx">{{ __('specialist-owner.reviews.items.' . $rev . '.text') }}</p>
        </article>
      @endforeach
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.reviews') }}">{!! $pencil !!}{{ __('specialist-owner.edit') }}</a>
  </section>

</main>

</x-layout>
