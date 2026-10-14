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

    // This page is the PUBLIC profile seen by its owner ("müştərilər onu belə görür"),
    // so every number on it has to be the number a visitor sees on specialist.blade.php.
    // The rating, the review count and the two review cards used to come straight from
    // the Figma copy deck, which told a brand-new master they had 4.9 stars and 416
    // reviews. Queried explicitly rather than through $profile->reviews because lazy
    // loading is disabled globally.
    $ownerReviews = $profile
        ? \App\Models\Review::query()
            ->where('reviewable_type', \App\Models\SpecialistProfile::class)
            ->where('reviewable_id', $profile->id)
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->get()
        : collect();
    $reviewsCount = $ownerReviews->count();
    $avgRating = $reviewsCount > 0 ? round((float) $ownerReviews->avg('rating'), 1) : 0.0;
@endphp
<x-layout page="specialist-owner" :title="t('specialist-owner.title', ['name' => $user->name])">

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

{{-- stats strip (831:11542) — DISABLED until the metrics behind it exist.
     The four tiles printed the Figma copy deck verbatim for every account:
     "1,284 baxış bu ay / +12%", "36 müraciət bu ay / +8%", "98% cavab nisbəti",
     "4.9 reytinq · 416 rəy". A master who registered a minute ago was shown all of
     it as their own performance. There is no profile-view, enquiry or response-time
     tracking in the schema yet, so none of these four can be rendered honestly;
     only `rating` is derivable, and a one-tile strip is not the Figma frame.
     Re-enable this block once real counters land, reading them from the profile. --}}
@if (false)
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
@endif

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
            {{-- specialist_profiles.is_verified, same gate the public page uses.
                 It used to render for everyone, so an unverified brand-new master
                 was shown a green "Təsdiqlənmiş" chip on their own profile. --}}
            @if ($profile?->is_verified)
              <span class="spo-badge ok">{{ t('specialist-owner.id.badge_verified') }}</span>
            @endif
          </div>
          <h1 class="spo-name">{{ $user->name }}</h1>
          <p class="spo-role">{{ $profile?->craft_label ?: t('specialist-owner.id.role') }}</p>
          @if ($reviewsCount > 0)
          <div class="spo-rate">
            <span class="v"><x-ui.stars :count="1" :rating="1" :icon="$starIcon" />{{ number_format($avgRating, 1) }}</span>
            <span class="r">({{ $reviewsCount }} {{ t('specialist.reviews.count_label', ['count' => $reviewsCount]) }})</span>
          </div>
          @endif
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
      {{-- "Cavab vaxtı ~1 saat" is Figma copy, not a measurement: nothing tracks how fast
           a master answers. Hidden with the stats strip until that data exists. --}}
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

  @if ($reviewsCount > 0)
  {{-- reviews — real approved reviews only. The two cards here used to be the Figma
       copy deck ("Aysel H.", "Elvin M."), shown to every master including one with no
       reviews at all, under a hardcoded 4.9 / 416-review summary. Empty now hides the
       section, exactly like the portfolio and services sections above. --}}
  <section class="spo-sec">
    <div class="spo-sechead">
      <x-ui.eyebrow variant="flat" class="spo-eyebrow gap-3" :label="t('specialist-owner.reviews.eyebrow')" />
      <h2>{{ t('specialist-owner.reviews.title') }}</h2>
    </div>
    <div class="spo-summ">
      <span class="n">{{ number_format($avgRating, 1) }}</span>
      <span class="c">
        <span class="ss"><x-ui.stars :rating="$avgRating" :icon="$starIcon" /></span>
        <span class="cnt">{{ $reviewsCount }} {{ t('specialist.reviews.count_label', ['count' => $reviewsCount]) }}</span>
      </span>
    </div>
    <div class="spo-revs">
      @foreach ($ownerReviews as $rev)
        <article class="spo-rev">
          <div class="hd">
            <span class="av" aria-hidden="true">{{ mb_strtoupper(mb_substr($rev->user->first_name ?? $rev->user->name ?? '?', 0, 1)) }}</span>
            <span class="nm"><span class="n">{{ $rev->user->name ?? t('specialist.reviews.anonymous') }}</span><span class="d">{{ $rev->created_at->translatedFormat('d M Y') }}</span></span>
          </div>
          <div class="st"><x-ui.stars :rating="$rev->rating" :icon="$starIcon" /></div>
          <p class="tx">{{ $rev->comment }}</p>
        </article>
      @endforeach
    </div>
    <a class="spo-edit" href="{{ route('specialist.cabinet.reviews') }}">{!! $pencil !!}{{ t('specialist-owner.edit') }}</a>
  </section>
  @endif

</main>

</x-layout>
