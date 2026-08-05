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
    $portfolioItems = $specialist->portfolioItems;
    $services = $specialist->services;
    $craft = translate_craft($specialist->craft) ?? __('specialist.id.role');
@endphp
<x-layout page="specialist" :title="$fullName . ' — ' . $craft">

<main class="pp">

  {{-- breadcrumb --}}
  <x-ui.breadcrumbs class="pp-crumbs text-sm" :items="[
      ['label' => __('specialist.crumb_home'), 'href' => route('home')],
      ['label' => __('specialist.crumb_specialists'), 'href' => route('specialists')],
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
            <span class="pp-badge"><img src="/assets/icon-crown-gold.svg" alt="">{{ __('specialist.id.badge_top') }}</span>
          @endif
          <button type="button" class="pp-fav" id="ppFav" data-on="false" aria-pressed="false" aria-label="{{ __('specialist.id.fav_aria') }}"><img src="/assets/icon-heart-pointed.svg" alt=""></button>
        </div>
        <div class="pp-id-r">
          @if ($user->isActive())
            <span class="pp-verified"><img src="/assets/icon-check-green.svg" alt="">{{ __('specialist.id.verified') }}</span>
          @endif
          <div class="pp-name">
            <div>
              <h1>{{ $fullName }}</h1>
              <p class="role">{{ $craft }}</p>
            </div>
            <div class="pp-rate">
              <span class="pp-stars"><x-ui.stars :rating="$avgRating" icon="/assets/icon-star-amber.svg" /></span>
              <span class="v">{{ $avgRating > 0 ? number_format($avgRating, 1) : '0.0' }}</span>
              <span class="r">({{ $reviewsCount }} {{ __('specialist.reviews.count_label', ['count' => $reviewsCount]) }})</span>
            </div>
          </div>
          <p class="pp-meta">
            @if ($specialist->experience_years)
              {{ __('specialist.meta.experience', ['years' => $specialist->experience_years]) }}
            @else
              {{ __('specialist.id.meta_exp') }}
            @endif
            <span class="dot">&middot;</span>
            {{ $portfolioItems->count() }} {{ __('specialist.meta.projects_label', ['count' => $portfolioItems->count()]) }}
            <span class="dot">&middot;</span>
            {{ $specialist->city ?? __('specialist.id.meta_city') }}
          </p>
        </div>
      </div>

      {{-- about --}}
      <section class="pp-about">
        <div class="pp-sechead">
          <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="__('specialist.about.eyebrow')" />
          <h2>{{ __('specialist.about.title') }}</h2>
        </div>
        <p class="txt">{{ $specialist->about ?? __('specialist.about.text') }}</p>
        @if (count($skills))
          <div class="pp-tags">
            @foreach ($skills as $skill)
              <span class="pp-tag">{{ $skill }}</span>
            @endforeach
          </div>
        @else
          <div class="pp-tags">
            @foreach (['t1', 't2', 't3', 't4', 't5', 't6'] as $tag)
              <span class="pp-tag">{{ __('specialist.about.tags.' . $tag) }}</span>
            @endforeach
          </div>
        @endif
      </section>

    </div>

    {{-- booking --}}
    <aside class="pp-book">
      <div class="ph">
        <span class="lbl">{{ __('specialist.book.label') }}</span>
        @if ($services->isNotEmpty())
          @php $minPrice = $services->min('price'); @endphp
          <div class="prow"><span class="now">{{ number_format($minPrice, 0) }} {{ __('sell.form.currency') }}</span><span class="sub">{{ __('specialist.book.price_sub') }}</span></div>
        @else
          <div class="prow"><span class="now">{{ __('specialist.book.price') }}</span><span class="sub">{{ __('specialist.book.price_sub') }}</span></div>
        @endif
      </div>
      <x-ui.button variant="primary" id="ppCalc" data-url="{{ route('calculator') }}"
          class="rounded-none px-6 py-4 text-base font-medium duration-200">{{ __('specialist.book.consult') }}</x-ui.button>
      <button class="pp-btn w" id="ppMsg"
              data-specialist-id="{{ $specialist->id }}"
              data-specialist-name="{{ $fullName }}">{{ __('specialist.book.message') }}</button>
      <div class="div"></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.response_k') }}</span><span class="v">{{ __('specialist.book.response_v') }}</span></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.done_k') }}</span><span class="v">{{ $portfolioItems->count() }}</span></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.member_k') }}</span><span class="v">{{ $specialist->created_at->translatedFormat('M Y') }}</span></div>
      <div class="pp-free"><span class="d"></span>{{ $specialist->is_on_vacation ? __('specialist.book.on_vacation') : __('specialist.book.free') }}</div>
    </aside>
  </div>

  {{-- portfolio --}}
  @if ($portfolioItems->isNotEmpty())
    <section class="pp-sec">
      <div class="pp-sechead">
        <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="__('specialist.portfolio.eyebrow')" />
        <h2>{{ __('specialist.portfolio.title') }}</h2>
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
              <span class="ov"><b>+{{ $portfolioItems->count() - 5 }}</b><span>{{ __('specialist.portfolio.more_link') }}</span></span>
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
        <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="__('specialist.services.eyebrow')" />
        <h2>{{ __('specialist.services.title') }}</h2>
      </div>
      <div class="pp-svc-list">
        @foreach ($services as $svc)
          <a class="pp-svc">
            <span class="l"><span class="t">{{ $svc->name }}</span><span class="s">{{ $svc->description ?? '' }}</span></span>
            <span class="rr"><span class="pr">{{ $svc->price ? '≈ ' . number_format($svc->price, 0) . ' ' . __('sell.form.currency') . ($svc->unit === 'sqm' ? '/m²' : ($svc->unit === 'metre' ? '/m' : '')) : '' }}</span><span class="ar">&rarr;</span></span>
          </a>
        @endforeach
      </div>
    </section>
  @else
    {{-- Fallback: show static placeholder services from translations --}}
    <section class="pp-sec">
      <div class="pp-sechead">
        <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="__('specialist.services.eyebrow')" />
        <h2>{{ __('specialist.services.title') }}</h2>
      </div>
      <div class="pp-svc-list">
        @foreach (['s1', 's2', 's3', 's4'] as $svc)
          <a class="pp-svc">
            <span class="l"><span class="t">{{ __('specialist.services.items.' . $svc . '.title') }}</span><span class="s">{{ __('specialist.services.items.' . $svc . '.sub') }}</span></span>
            <span class="rr"><span class="pr">{{ __('specialist.services.items.' . $svc . '.price') }}</span><span class="ar">&rarr;</span></span>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  {{-- reviews --}}
  <section class="pp-sec">
    <div class="pp-sechead">
      <x-ui.eyebrow variant="flat" class="pp-eyebrow gap-3" :label="__('specialist.reviews.eyebrow')" />
      <h2>{{ __('specialist.reviews.title') }}</h2>
    </div>
    <div class="pp-score">
      <span class="n">{{ $avgRating > 0 ? number_format($avgRating, 1) : '0.0' }}</span>
      <span class="c">
        <span class="ss"><x-ui.stars :rating="$avgRating" /></span>
        <span class="cnt">{{ $reviewsCount }} {{ __('specialist.reviews.count_label', ['count' => $reviewsCount]) }}</span>
      </span>
    </div>
    <div class="pp-revs">
      @forelse ($reviews as $rev)
        <article class="pp-rev">
          <div class="hd">
            <span class="av">{{ mb_strtoupper(mb_substr($rev->user->first_name ?? $rev->user->name ?? '?', 0, 1)) }}</span>
            <span class="nm"><span class="n">{{ $rev->user->name ?? __('specialist.reviews.anonymous') }}</span><span class="d">{{ $rev->created_at->translatedFormat('d M Y') }}</span></span>
          </div>
          <div class="st"><x-ui.stars :rating="$rev->rating" /></div>
          <p class="tx">{{ $rev->comment }}</p>
        </article>
      @empty
        <p class="text-gray-500">{{ __('specialist.reviews.empty') }}</p>
      @endforelse
    </div>
    @if ($reviewsCount > 5)
      <a class="sec-more"><p>{{ __('specialist.reviews.more') }}</p><img src="/assets/icon-arrow-right.svg" alt=""></a>
    @endif
  </section>

</main>

{{-- Message modal --}}
<x-ui.modal id="msgModal" aria-labelledby="msgTitle" :close-label="__('specialist.book.close', [], 'Bağla')"
            dialog="w-full max-w-[520px] animate-[lmIn_0.26s_ease] bg-white px-9 py-10 shadow-[-6px_6px_28px_rgba(0,0,0,0.22)]">
  <h2 class="mb-2 text-xl font-semibold text-ink" id="msgTitle">{{ __('specialist.book.message_title', [], 'Mesaj göndər') }}</h2>
  <p class="mb-5 text-sm text-black/55">{{ __('specialist.book.message_to', [], 'Kimə:') }} <b id="msgRecipient">{{ $fullName }}</b></p>

  <form id="msgForm">
    @auth
    <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
    @endauth

    <div class="flex flex-col gap-4">
      @guest
        <div class="flex gap-3 max-[560px]:flex-col">
          <div class="flex flex-1 flex-col gap-1">
            <label class="text-sm font-medium text-black/70" for="msgName">{{ __('specialist.book.your_name', [], 'Adınız') }} *</label>
            <input type="text" id="msgName" name="name" required
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40"
                   placeholder="{{ __('specialist.book.name_placeholder', [], 'Ad və soyad') }}">
          </div>
          <div class="flex flex-1 flex-col gap-1">
            <label class="text-sm font-medium text-black/70" for="msgPhone">{{ __('specialist.book.your_phone', [], 'Telefon') }} *</label>
            <input type="tel" id="msgPhone" name="phone" required
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40"
                   placeholder="+994 XX XXX XX XX">
          </div>
        </div>
      @endguest

      <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-black/70" for="msgText">{{ __('specialist.book.message_label', [], 'Mesajınız') }} *</label>
        <textarea id="msgText" name="message" required rows="4"
                  class="resize-y border border-black/15 px-4 py-3 text-[15px] leading-[1.5] outline-none transition focus:border-black/40"
                  placeholder="{{ __('specialist.book.message_placeholder', [], 'Sualınızı və ya layihəniz haqqında məlumatı yazın...') }}"></textarea>
      </div>
    </div>

    <div class="mt-6 flex flex-col gap-3">
      <button type="submit" id="msgSubmit"
              class="flex h-[50px] items-center justify-center bg-yellow text-base font-semibold text-ink transition duration-200 hover:brightness-[.93]">{{ __('specialist.book.send', [], 'Göndər') }}</button>
    </div>

    <div class="mt-4 hidden border border-green/20 bg-green/5 px-4 py-3 text-center text-sm text-green-700" id="msgSuccess">
      {{ __('specialist.book.message_sent', [], 'Mesajınız uğurla göndərildi!') }}
    </div>
    <div class="mt-4 hidden border border-red/20 bg-red/5 px-4 py-3 text-center text-sm text-red" id="msgError"></div>
  </form>
</x-ui.modal>

</x-layout>
