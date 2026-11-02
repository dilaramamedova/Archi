{{--
  One company card — used by the /b2b directory grid and the "related companies" row
  on the profile page. Expects: $company (B2bCompany, category loaded) and $tOr
  (the translate-with-fallback closure defined at the top of each page).
  Styles: resources/css/pages/b2b.css (.b2-card*)
--}}
@php
    $coop = $company->cooperation_type?->translated();
    $desc = trim(strip_tags((string) $company->description));
    $url = route('b2b.show', $company->slug);
    $plan = $company->plan;
@endphp
<article class="b2-card">
  <div class="b2-card-top">
    @if ($company->logo_url)
      <img class="b2-logo" src="{{ $company->logo_url }}" alt="{{ $company->displayName() }}" loading="lazy">
    @else
      <span class="b2-logo" aria-hidden="true">{{ $company->initials() }}</span>
    @endif

    <div class="b2-card-head">
      <h3 class="b2-card-name"><a href="{{ $url }}">{{ $company->displayName() }}</a></h3>
      <div class="b2-card-chips">
        @if ($company->country)
          <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'pin'])<span>{{ $company->country }}</span></span>
        @endif
        @if ($coop)
          <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'briefcase'])<span>{{ $coop }}</span></span>
        @endif
      </div>
    </div>

    <div class="b2-card-badges">
      @if ($company->is_verified)
        <span class="b2-badge b2-badge-ok" title="{{ $tOr('b2b.badge.verified', 'Təsdiqlənib') }}">
          @include('pages.partials.b2b-icon', ['name' => 'badge-check', 'stroke' => '1.6'])
          <span>{{ $tOr('b2b.badge.verified', 'Təsdiqlənib') }}</span>
        </span>
      @endif
      @if ($company->is_featured)
        <span class="b2-badge b2-badge-feat">
          @include('pages.partials.b2b-icon', ['name' => 'star', 'stroke' => '1.4'])
          <span>{{ $tOr('b2b.badge.featured', 'Seçilmiş') }}</span>
        </span>
      @endif
      @if ($plan?->badge())
        <span class="b2-badge b2-badge-plan">{{ $tOr('b2b.plan_tag.'.$plan->value, $plan->label()) }}</span>
      @endif
    </div>
  </div>

  @if ($desc !== '')
    <p class="b2-card-desc">{{ \Illuminate\Support\Str::limit($desc, 150) }}</p>
  @endif

  <div class="b2-card-foot">
    @if ($company->category)
      <span class="b2-card-cat">@include('pages.partials.b2b-icon', ['name' => 'layers', 'stroke' => '1.6'])<span>{{ $company->category->name }}</span></span>
    @else
      <span></span>
    @endif
    <a class="b2-more" href="{{ $url }}" aria-label="{{ $tOr('b2b.card.more', 'Ətraflı') }}: {{ $company->displayName() }}">
      <span>{{ $tOr('b2b.card.more', 'Ətraflı') }}</span>
      @include('pages.partials.b2b-icon', ['name' => 'arrow-right'])
    </a>
  </div>
</article>
