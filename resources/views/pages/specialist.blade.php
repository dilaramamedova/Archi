{{--
  Specialist profile (Figma 777:2779, `pp-` prefix). Now driven by the $specialist
  model passed from SpecialistController@show. Falls back to translation strings
  for static labels; dynamic data comes from SpecialistProfile + relations.
--}}
@php
    $user = $specialist->user;
    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
    $initials = mb_strtoupper(mb_substr($user->first_name ?? $user->name, 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
    $avatarUrl = $specialist->avatar_path ? storage_url($specialist->avatar_path) : '/assets/avatar-placeholder.png';
    $skills = is_array($specialist->skills) ? $specialist->skills : [];
    $portfolioItems = $specialist->approvedPortfolioItems;
    $services = $specialist->services;
    $craft = $specialist->craft_label ?: t('specialist.id.role');
    // The portfolio holds photos, not jobs — label it as portfolio work, never as
    // "completed projects". Dropped entirely until the honest label exists.
    $hasPortfolioLabel = \Illuminate\Support\Facades\Lang::has('specialist.meta.portfolio_label');
    $metaParts = collect([
        $specialist->experience_years ? t('specialist.meta.experience', ['years' => $specialist->experience_years]) : null,
        $portfolioItems->isNotEmpty() && $hasPortfolioLabel
            ? $portfolioItems->count() . ' ' . t('specialist.meta.portfolio_label', ['count' => $portfolioItems->count()])
            : null,
        \App\Enums\City::display($specialist->city),
    ])->filter()->values();
@endphp
<x-layout page="specialist" :title="$fullName . ' — ' . $craft">

<main class="pp">

  {{-- breadcrumb --}}
  <x-ui.breadcrumbs class="pp-crumbs text-sm" :items="[
      ['label' => t('specialist.crumb_home'), 'href' => route('home')],
      ['label' => t('specialist.crumb_specialists'), 'href' => route('specialists')],
      ['label' => $fullName],
  ]" />

  {{-- header --}}
  <div class="pp-head">
    <div class="pp-head-l">

      {{-- identity --}}
      <div class="pp-id">
        <div class="pp-ava">
          <div class="ring"><img src="{{ $avatarUrl }}" alt="{{ $fullName }}"></div>
          @if ($specialist->is_featured)
            <span class="pp-badge"><img src="/assets/icon-crown-gold.svg" alt="">{{ t('specialist.id.badge_top') }}</span>
          @endif
          <button type="button" class="pp-fav" id="ppFav" data-on="false" aria-pressed="false" aria-label="{{ t('specialist.id.fav_aria') }}"><img src="/assets/icon-heart-pointed.svg" alt=""></button>
        </div>
        <div class="pp-id-r">
          {{-- "Təsdiqlənmiş" needs a real admin-set verification flag; the page already
               404s for non-active accounts, so isActive() said nothing. Hidden until
               specialist_profiles.is_verified exists. --}}
          <div class="pp-name">
            <div>
              <h1>{{ $fullName }}</h1>
              <p class="role">{{ $craft }}</p>
            </div>
            <div class="pp-rate">
              <span class="pp-stars"><x-ui.stars :rating="$avgRating" icon="/assets/icon-star-amber.svg" /></span>
              <span class="v">{{ $avgRating > 0 ? number_format($avgRating, 1) : '0.0' }}</span>
              <span class="r">({{ $reviewsCount }} {{ t('specialist.reviews.count_label', ['count' => $reviewsCount]) }})</span>
            </div>
          </div>
          @if ($metaParts->isNotEmpty())
            <p class="pp-meta">
              @foreach ($metaParts as $part)
                @if (! $loop->first)<span class="dot">&middot;</span>@endif
                {{ $part }}
              @endforeach
            </p>
          @endif
        </div>
      </div>

      @if (filled($specialist->about) || count($skills))
      {{-- about --}}
      <section class="pp-about">
        <div class="pp-sechead">
          <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="t('specialist.about.eyebrow')" />
          <h2>{{ t('specialist.about.title') }}</h2>
        </div>
        @if (filled($specialist->about))
          <p class="txt">{{ $specialist->about }}</p>
        @endif
        @if (count($skills))
          <div class="pp-tags">
            @foreach ($skills as $skill)
              <span class="pp-tag">{{ $skill }}</span>
            @endforeach
          </div>
        @endif
      </section>
      @endif

    </div>

    {{-- booking --}}
    <aside class="pp-book">
      @if (false)
      <div class="ph">
        <span class="lbl">{{ t('specialist.book.label') }}</span>
        @if ($services->isNotEmpty())
          @php $minPrice = $services->min('price'); @endphp
          <div class="prow"><span class="now">{{ number_format($minPrice, 0) }} {{ t('sell.form.currency') }}</span><span class="sub">{{ t('specialist.book.price_sub') }}</span></div>
        @else
          <div class="prow"><span class="now">{{ t('specialist.book.price') }}</span><span class="sub">{{ t('specialist.book.price_sub') }}</span></div>
        @endif
      </div>
      @endif
      {{-- The number itself never reaches the public HTML: signed-in visitors fetch it
           from the throttled JSON endpoint on click, guests get [data-login] and the
           shared login modal opens instead. --}}
      @php
          $contactAttrs = new \Illuminate\View\ComponentAttributeBag(auth()->check()
              ? ['data-phone-url' => url('/api/specialist/' . $specialist->id . '/phone')]
              : ['data-login' => true]);
      @endphp
      <x-ui.button variant="primary" id="ppContact" :attributes="$contactAttrs"
          data-no-phone="{{ t('specialist.contact_no_phone') }}"
          class="rounded-none px-6 py-4 text-base font-medium duration-200">{{ t('specialist.contact') }}</x-ui.button>
      <a id="ppContactPhone" class="pp-contact-phone" href="#" hidden></a>
      <div class="div"></div>
      <div class="pp-stat"><span class="k">{{ t('specialist.book.member_k') }}</span><span class="v">{{ $specialist->created_at->translatedFormat('M Y') }}</span></div>
      @if ($specialist->is_on_vacation)
        <div class="pp-free"><span class="d"></span>{{ t('specialist.book.on_vacation') }}</div>
      @elseif ($specialist->available_slots > 0 && \Illuminate\Support\Facades\Lang::has('specialist.book.slots_left'))
        <div class="pp-free"><span class="d"></span>{{ t('specialist.book.slots_left', ['count' => $specialist->available_slots]) }}</div>
      @endif
    </aside>
  </div>

  {{-- portfolio --}}
  @if ($portfolioItems->isNotEmpty())
    <section class="pp-sec">
      <div class="pp-sechead">
        <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="t('specialist.portfolio.eyebrow')" />
        <h2>{{ t('specialist.portfolio.title') }}</h2>
      </div>
      <div class="pp-grid">
        <div class="pp-grow">
          @foreach ($portfolioItems->take(3) as $item)
            <a class="pp-tile"><img src="{{ storage_url($item->image_path) }}" alt="{{ $item->title ?? '' }}"><span class="lb">{{ $item->title ?? '' }}</span></a>
          @endforeach
        </div>
        <div class="pp-grow">
          @foreach ($portfolioItems->slice(3, 2) as $item)
            <a class="pp-tile"><img src="{{ storage_url($item->image_path) }}" alt="{{ $item->title ?? '' }}"><span class="lb">{{ $item->title ?? '' }}</span></a>
          @endforeach
          @if ($portfolioItems->count() > 5)
            <a class="pp-tile more">
              <img src="{{ storage_url($portfolioItems->get(5)?->image_path) }}" alt="">
              <span class="ov"><b>+{{ $portfolioItems->count() - 5 }}</b><span>{{ t('specialist.portfolio.more_link') }}</span></span>
            </a>
          @endif
        </div>
      </div>
    </section>
  @endif

  {{-- services --}}
  @if ($services->isNotEmpty())
    <section class="pp-sec">
      <div class="pp-sechead">
        <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="t('specialist.services.eyebrow')" />
        <h2>{{ t('specialist.services.title') }}</h2>
      </div>
      <div class="pp-svc-list">
        @foreach ($services as $svc)
          <a class="pp-svc">
            <span class="l"><span class="t">{{ $svc->name }}</span><span class="s">{{ $svc->description ?? '' }}</span></span>
            <span class="rr"><span class="pr">{{ $svc->price ? '≈ ' . number_format($svc->price, 0) . ' ' . t('sell.form.currency') . ($svc->unit === 'sqm' ? '/m²' : ($svc->unit === 'metre' ? '/m' : '')) : '' }}</span><span class="ar">&rarr;</span></span>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  @if (false)
  {{-- Müştəri rəyləri bölməsi hazırda göstərilmir. --}}
  <section class="pp-sec">
    <div class="pp-sechead">
      <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="t('specialist.reviews.eyebrow')" />
      <h2>{{ t('specialist.reviews.title') }}</h2>
    </div>
    <div class="pp-score">
      <span class="n">{{ $avgRating > 0 ? number_format($avgRating, 1) : '0.0' }}</span>
      <span class="c">
        <span class="ss"><x-ui.stars :rating="$avgRating" /></span>
        <span class="cnt">{{ $reviewsCount }} {{ t('specialist.reviews.count_label', ['count' => $reviewsCount]) }}</span>
      </span>
    </div>
    <div class="pp-revs">
      @forelse ($reviews as $rev)
        <article class="pp-rev">
          <div class="hd">
            <span class="av">{{ mb_strtoupper(mb_substr($rev->user->first_name ?? $rev->user->name ?? '?', 0, 1)) }}</span>
            <span class="nm"><span class="n">{{ $rev->user->name ?? t('specialist.reviews.anonymous') }}</span><span class="d">{{ $rev->created_at->translatedFormat('d M Y') }}</span></span>
          </div>
          <div class="st"><x-ui.stars :rating="$rev->rating" /></div>
          <p class="tx">{{ $rev->comment }}</p>
        </article>
      @empty
        <p class="text-gray-500">{{ t('specialist.reviews.empty') }}</p>
      @endforelse
    </div>
    @if ($reviewsCount > 5)
      <a class="sec-more"><p>{{ t('specialist.reviews.more') }}</p><img src="/assets/icon-arrow-right.svg" alt=""></a>
    @endif
  </section>
  @endif

</main>

</x-layout>
