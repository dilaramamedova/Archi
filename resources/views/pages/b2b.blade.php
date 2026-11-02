{{--
  B2B directory (Figma «10 · B2B BEYNƏLXALQ / ArchiAds»). One public page:
    1. a pricing hero + plan cards (international USD / local AZN, switched by a
       market toggle) — the plan CTAs open the consultation lead modal, there is
       no self-registration;
    2. the company directory — search + filter sidebar/bottom-sheet + sort + grid,
       mirroring the tender board (pages/tenders).
  Styles: resources/css/pages/b2b.css   JS: resources/js/pages/b2b.js
--}}
@php
  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  // feature = [enabled, key, az-fallback]
  $f = fn (bool $on, string $key, string $az): array => ['on' => $on, 'key' => $key, 'az' => $az];

  // ---- INTERNATIONAL (USD) ----
  $intlPlans = [
    [
      'id' => 'free', 'badge' => false, 'name' => $tOr('b2b.plan.free.name', 'FREE'),
      'tag' => $tOr('b2b.plan.free.tag_intl', 'Be Present in Azerbaijan'),
      'price' => '0', 'per' => $tOr('b2b.plan.per_usd', 'USD/ay'),
      'cta' => $tOr('b2b.cta.start_free', 'Start Free'), 'cta_plan' => 'FREE (beynəlxalq)',
      'feats' => [
        $f(true, 'public_profile', 'Açıq şirkət profili'),
        $f(true, 'logo_web_contacts', 'Logo / Veb-sayt / Əlaqə'),
        $f(true, 'short_desc', 'Qısa təsvir'),
        $f(true, 'cats_2_10', '2 kateqoriya, 10 məhsul'),
        $f(true, 'pdf_1', '1 PDF kataloq'),
        $f(true, 'b2b_requests', 'B2B sorğuları'),
        $f(true, 'basic_cooperation', 'Əsas əməkdaşlıq növləri'),
        $f(true, 'basic_moq', 'Əsas MOQ məlumatı'),
        $f(false, 'extended_profile', 'Genişləndirilmiş profil / tarixçə'),
        $f(false, 'production_capacity', 'İstehsal gücü / ixrac bazarları'),
        $f(false, 'certificates', 'Sertifikatlar / mükafatlar / layihələr'),
        $f(false, 'video', 'Video'),
        $f(false, 'analytics_full', 'Analitika (Profil/Məhsul/Kataloq/Lead)'),
        $f(false, 'geography', 'Coğrafiya / qayıdan ziyarətçilər'),
        $f(false, 'search_priority', 'Axtarışda prioritet'),
        $f(false, 'recommended', 'Tövsiyə olunan şirkətlər'),
        $f(false, 'featured_eligibility', 'Seçilmiş olma imkanı'),
        $f(false, 'promotion_tools', 'Təşviq alətləri'),
      ],
    ],
    [
      'id' => 'premium', 'badge' => true, 'highlight' => true, 'name' => $tOr('b2b.plan.b2b_premium.name', 'B2B PREMIUM'),
      'tag' => $tOr('b2b.plan.b2b_premium.tag', 'Find Your Partner in Azerbaijan'),
      'price' => '99', 'per' => $tOr('b2b.plan.per_usd', 'USD/ay'),
      'cta' => $tOr('b2b.cta.trial', 'Start 14-day Trial'), 'cta_plan' => 'B2B PREMIUM (beynəlxalq, 99 USD/ay)',
      'feats' => [
        $f(true, 'extended_profile_history', 'Genişləndirilmiş şirkət profili və tarixçəsi'),
        $f(true, 'production_capacity', 'İstehsal gücü / ixrac bazarları'),
        $f(true, 'certificates', 'Sertifikatlar / mükafatlar / layihələr'),
        $f(true, 'cats_30_500', '30 kateqoriya, 500 məhsul'),
        $f(true, 'pdf_30', '30 PDF kataloq'),
        $f(true, 'video', 'Video'),
        $f(true, 'price_range', 'Kateqoriya/məhsul üzrə qiymət aralığı'),
        $f(true, 'extended_moq', 'Genişləndirilmiş MOQ'),
        $f(true, 'all_cooperation', 'Bütün əməkdaşlıq növləri'),
        $f(true, 'b2b_requests', 'B2B sorğuları'),
        $f(true, 'full_analytics', 'Tam analitika (Profil/Məhsul/Kataloq/Lead)'),
        $f(true, 'geography', 'Coğrafiya / qayıdan ziyarətçilər'),
        $f(true, 'search_priority', 'Axtarışda prioritet'),
        $f(true, 'recommended', 'Tövsiyə olunan şirkətlər'),
        $f(true, 'featured_eligibility_incl', 'Seçilmiş olma (abunəyə daxildir)'),
        $f(true, 'promotion_tools_incl', 'Təşviq alətləri (abunəyə daxildir)'),
      ],
    ],
  ];

  // ---- LOCAL (AZN) ----
  $localPlans = [
    [
      'id' => 'free', 'badge' => false, 'popular' => false, 'name' => $tOr('b2b.plan.free.name', 'FREE'),
      'tag' => $tOr('b2b.plan.free.tag_local', 'Be Present'),
      'price' => '0', 'per' => $tOr('b2b.plan.per_azn', 'AZN/ay'),
      'cta' => $tOr('b2b.cta.start_free', 'Start Free'), 'cta_plan' => 'FREE (daxili bazar)',
      'feats' => [
        $f(true, 'cats_2', '2 kateqoriya'),
        $f(true, 'products_10', '10 məhsul'),
        $f(true, 'catalog_1', '1 kataloq'),
        $f(true, 'limited_foto', 'Məhdud foto'),
        $f(true, 'basic_product_feats', 'Əsas məhsul xüsusiyyətləri'),
        $f(true, 'requests', 'Sorğular'),
        $f(false, 'video', 'Video'),
        $f(false, 'analitika', 'Analitika'),
        $f(false, 'search_priority_l', 'Axtarışda prioritet'),
        $f(false, 'featured_company', 'Seçilmiş şirkət'),
        $f(false, 'featured_product', 'Seçilmiş məhsul'),
        $f(false, 'category_promotion', 'Kateqoriya təşviqi'),
        $f(false, 'homepage_placement', 'Ana səhifədə yerləşdirmə'),
        $f(false, 'advertising', 'Reklam'),
      ],
    ],
    [
      'id' => 'basic', 'badge' => false, 'popular' => false, 'name' => $tOr('b2b.plan.basic.name', 'BASIC'),
      'tag' => $tOr('b2b.plan.basic.tag', 'Get Started'),
      'price' => '29', 'per' => $tOr('b2b.plan.per_azn', 'AZN/ay'),
      'cta' => $tOr('b2b.cta.choose_basic', 'Choose Basic'), 'cta_plan' => 'BASIC (daxili bazar, 29 AZN/ay)',
      'feats' => [
        $f(true, 'cats_5', '5 kateqoriya'),
        $f(true, 'products_50', '50 məhsul'),
        $f(true, 'catalog_3', '3 kataloq'),
        $f(true, 'more_foto', 'Daha çox foto'),
        $f(true, 'video', 'Video'),
        $f(true, 'product_feats', 'Məhsul xüsusiyyətləri'),
        $f(true, 'requests', 'Sorğular'),
        $f(true, 'basic_analitika', 'Əsas analitika'),
        $f(false, 'search_priority_l', 'Axtarışda prioritet'),
        $f(false, 'featured_company', 'Seçilmiş şirkət'),
        $f(false, 'featured_product', 'Seçilmiş məhsul'),
        $f(false, 'category_promotion', 'Kateqoriya təşviqi'),
        $f(false, 'homepage_placement', 'Ana səhifədə yerləşdirmə'),
        $f(true, 'advertising_addon', 'Reklam (əlavə)'),
      ],
    ],
    [
      'id' => 'pro', 'badge' => true, 'popular' => true, 'name' => $tOr('b2b.plan.pro.name', 'PRO'),
      'tag' => $tOr('b2b.plan.pro.tag', 'Get More Customers'),
      'price' => '59', 'per' => $tOr('b2b.plan.per_azn', 'AZN/ay'),
      'cta' => $tOr('b2b.cta.choose_pro', 'Choose Pro'), 'cta_plan' => 'PRO (daxili bazar, 59 AZN/ay)',
      'feats' => [
        $f(true, 'cats_15', '15 kateqoriya'),
        $f(true, 'products_200', '200 məhsul'),
        $f(true, 'catalog_10', '10 kataloq'),
        $f(true, 'extended_foto', 'Genişləndirilmiş foto'),
        $f(true, 'video', 'Video'),
        $f(true, 'product_feats', 'Məhsul xüsusiyyətləri'),
        $f(true, 'requests', 'Sorğular'),
        $f(true, 'advanced_analitika', 'Təkmil analitika'),
        $f(true, 'search_priority_up', 'Axtarışda prioritet ↑'),
        $f(false, 'featured_company', 'Seçilmiş şirkət'),
        $f(true, 'featured_product_addon', 'Seçilmiş məhsul (əlavə)'),
        $f(true, 'category_promotion_addon', 'Kateqoriya təşviqi (əlavə)'),
        $f(false, 'homepage_placement', 'Ana səhifədə yerləşdirmə'),
        $f(true, 'advertising_addon', 'Reklam (əlavə)'),
      ],
    ],
    [
      'id' => 'premium', 'badge' => true, 'popular' => false, 'name' => $tOr('b2b.plan.premium.name', 'PREMIUM'),
      'tag' => $tOr('b2b.plan.premium.tag', 'Grow Your Business'),
      'price' => '119', 'per' => $tOr('b2b.plan.per_azn', 'AZN/ay'),
      'cta' => $tOr('b2b.cta.choose_premium', 'Choose Premium'), 'cta_plan' => 'PREMIUM (daxili bazar, 119 AZN/ay)',
      'feats' => [
        $f(true, 'cats_30plus', '30+ kateqoriya'),
        $f(true, 'products_500plus', '500+ məhsul'),
        $f(true, 'catalog_30plus', '30+ kataloq'),
        $f(true, 'maximum_foto', 'Maksimum foto'),
        $f(true, 'video', 'Video'),
        $f(true, 'product_feats', 'Məhsul xüsusiyyətləri'),
        $f(true, 'requests', 'Sorğular'),
        $f(true, 'full_analitika', 'Tam analitika'),
        $f(true, 'search_priority_up2', 'Axtarışda prioritet ↑↑'),
        $f(true, 'featured_company_sec11', 'Seçilmiş şirkət'),
        $f(true, 'featured_product', 'Seçilmiş məhsul'),
        $f(true, 'category_promotion_double', 'Kateqoriya təşviqi ✓✓'),
        $f(true, 'homepage_placement', 'Ana səhifədə yerləşdirmə'),
        $f(true, 'advertising_extended', 'Reklam (genişləndirilmiş)'),
      ],
    ],
  ];

  $market = $filters['market'];
  $sortOptions = [
      'featured' => $tOr('b2b.sort.featured', 'Seçilmişlər öncə'),
      'newest' => $tOr('b2b.sort.newest', 'Ən yeni'),
      'name' => $tOr('b2b.sort.name', 'Ad (A–Z)'),
  ];
  $hasCriteria = $filters['q'] !== '' || $activeFilterCount > 0;
  $resetUrl = route('b2b.index', ['market' => $market]);

  // Pricing (hero, plan cards, plan lead modal) is switched off for now — the
  // markup stays below; set this to true to bring it back.
  $showPricing = false;
@endphp
<x-layout page="b2b" :title="$tOr('b2b.meta.title', 'B2B — ARCHİ')">

<main class="b2-page">

  @if ($showPricing)
  {{-- ============================ PRICING HERO ============================ --}}
  <section class="b2-hero" data-market="{{ $market }}">
    <div class="wrap"><div class="inner b2-hero-inner">
      <x-ui.breadcrumbs class="b2-crumbs" :items="[
          ['label' => t('common.home'), 'href' => route('home')],
          ['label' => $tOr('b2b.crumb', 'B2B')],
      ]" />

      {{-- market toggle --}}
      <div class="b2-toggle" role="tablist" aria-label="{{ $tOr('b2b.market.label', 'Bazar') }}">
        <button type="button" class="b2-toggle-btn" role="tab" data-market-btn="international"
                aria-selected="{{ $market === 'international' ? 'true' : 'false' }}">{{ $tOr('b2b.market.international', 'Beynəlxalq (USD)') }}</button>
        <button type="button" class="b2-toggle-btn" role="tab" data-market-btn="local"
                aria-selected="{{ $market === 'local' ? 'true' : 'false' }}">{{ $tOr('b2b.market.local', 'Daxili bazar (AZN)') }}</button>
      </div>

      <div class="b2-hero-head" data-market-head="international" @if ($market !== 'international') hidden @endif>
        <p class="b2-eyebrow">{{ $tOr('b2b.hero.intl.eyebrow', 'B2B INTERNATIONAL') }}</p>
        <h1>{{ $tOr('b2b.hero.intl.title', 'Find Your Partner in Azerbaijan') }}</h1>
        <p class="b2-hero-sub">{{ $tOr('b2b.hero.intl.subtitle', 'Xarici istehsalçılar və brendlər üçün — Azərbaycan bazarında tərəfdaş tapın.') }}</p>
      </div>
      <div class="b2-hero-head" data-market-head="local" @if ($market !== 'local') hidden @endif>
        <p class="b2-eyebrow">{{ $tOr('b2b.hero.local.eyebrow', 'DAXİLİ BAZAR') }}</p>
        <h1>{{ $tOr('b2b.hero.local.title', 'Tariffs for Azerbaijan Market') }}</h1>
        <p class="b2-hero-sub">{{ $tOr('b2b.hero.local.subtitle', 'Daha çox görünürlük. Daha çox müştəri. Daha çox imkan.') }}</p>
      </div>
    </div></div>
  </section>

  {{-- ============================ PLAN CARDS ============================ --}}
  <div class="wrap"><div class="inner">
    {{-- international: 2 cards --}}
    <div class="b2-plans b2-plans-2" data-market-plans="international" @if ($market !== 'international') hidden @endif>
      @foreach ($intlPlans as $plan)
        @include('pages.partials.b2b-plan', ['plan' => $plan, 'tOr' => $tOr, 'planKey' => 'international.'.$plan['id']])
      @endforeach
    </div>
    {{-- local: 4 cards --}}
    <div class="b2-plans b2-plans-4" data-market-plans="local" @if ($market !== 'local') hidden @endif>
      @foreach ($localPlans as $plan)
        @include('pages.partials.b2b-plan', ['plan' => $plan, 'tOr' => $tOr, 'planKey' => 'local.'.$plan['id']])
      @endforeach
    </div>
  </div></div>
  @endif

  {{-- ============================ DIRECTORY ============================ --}}
  <div class="wrap"><div class="inner b2-dir" @unless ($showPricing) data-solo @endunless>

    @unless ($showPricing)
      <x-ui.breadcrumbs class="b2-crumbs" :items="[
          ['label' => t('common.home'), 'href' => route('home')],
          ['label' => $tOr('b2b.crumb', 'B2B')],
      ]" />
    @endunless

    <div class="b2-dir-head">
      {{-- Without the pricing hero, the directory heading is the page's h1. --}}
      <h{{ $showPricing ? 2 : 1 }} class="b2-dir-title">{{ $tOr('b2b.dir.heading', 'Şirkət kataloqu') }}</h{{ $showPricing ? 2 : 1 }}>
      <p>{{ $tOr('b2b.dir.subtitle', 'Azərbaycan bazarında fəaliyyət göstərən istehsalçı, distribütor və tədarükçüləri tapın.') }}</p>
    </div>

    <form class="b2-form" id="b2Form" method="get" action="{{ route('b2b.index') }}" role="search">
      <input type="hidden" name="market" id="b2MarketInput" value="{{ $market }}">

      {{-- search row --}}
      <div class="b2-search">
        <label class="b2-search-field">
          @include('pages.partials.b2b-icon', ['name' => 'search'])
          <span class="sr-only">{{ $tOr('b2b.search.label', 'Şirkət axtarışı') }}</span>
          <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="120"
                 placeholder="{{ $tOr('b2b.search.placeholder', 'Şirkət və ya brend axtarın') }}">
        </label>
        <button type="submit" class="b2-btn b2-btn-primary b2-search-btn">{{ $tOr('b2b.search.submit', 'Axtar') }}</button>
      </div>

      <div class="b2-body">
        {{-- filters: sidebar ≥981px, bottom sheet below --}}
        <aside class="b2-filters" id="b2Filters" data-open="false" aria-labelledby="b2FiltersTitle">
          <div class="b2-filters-head">
            <h3 id="b2FiltersTitle">{{ $tOr('b2b.filters.title', 'Filtrlər') }}</h3>
            <button type="button" class="b2-filters-close" id="b2FiltersClose" aria-label="{{ t('common.close') }}">
              @include('pages.partials.b2b-icon', ['name' => 'x'])
            </button>
          </div>

          <div class="b2-filters-scroll">
            @if ($categories->isNotEmpty())
              <fieldset class="b2-fgroup">
                <legend>{{ $tOr('b2b.filters.category', 'Kateqoriya') }}</legend>
                @foreach ($categories as $category)
                  <label class="b2-check">
                    <input type="checkbox" name="category[]" value="{{ $category->slug }}"
                           @checked(in_array($category->slug, $filters['categories'], true))>
                    <span class="box" aria-hidden="true">@include('pages.partials.b2b-icon', ['name' => 'check', 'stroke' => '2.6'])</span>
                    <span class="lbl">{{ $category->name }}</span>
                    <span class="n">{{ $category->companies_count }}</span>
                  </label>
                @endforeach
              </fieldset>
              <hr class="b2-fdiv">
            @endif

            <div class="b2-fgroup">
              <label class="b2-flabel" for="b2Country">{{ $tOr('b2b.filters.country', 'Ölkə') }}</label>
              <div class="b2-select">
                <select id="b2Country" name="country">
                  <option value="">{{ $tOr('b2b.filters.country_all', 'Bütün ölkələr') }}</option>
                  @foreach ($countries as $country)
                    <option value="{{ $country }}" @selected($filters['country'] === $country)>{{ $country }}</option>
                  @endforeach
                </select>
                @include('pages.partials.b2b-icon', ['name' => 'chevron-down'])
              </div>
            </div>

            <hr class="b2-fdiv">

            <div class="b2-fgroup">
              <label class="b2-flabel" for="b2Coop">{{ $tOr('b2b.filters.cooperation', 'Əməkdaşlıq növü') }}</label>
              <div class="b2-select">
                <select id="b2Coop" name="cooperation">
                  <option value="">{{ $tOr('b2b.filters.cooperation_all', 'Bütün növlər') }}</option>
                  @foreach ($cooperationTypes as $type)
                    <option value="{{ $type->value }}" @selected($filters['cooperation'] === $type->value)>{{ $type->translated() }}</option>
                  @endforeach
                </select>
                @include('pages.partials.b2b-icon', ['name' => 'chevron-down'])
              </div>
            </div>
          </div>

          <div class="b2-filters-foot">
            <button type="submit" class="b2-btn b2-btn-primary b2-apply">{{ $tOr('b2b.filters.apply', 'Filtrləri tətbiq et') }}</button>
            <a class="b2-reset" href="{{ $resetUrl }}">{{ $tOr('b2b.filters.reset', 'Sıfırla') }}</a>
          </div>
        </aside>
        <div class="b2-scrim" id="b2Scrim" hidden></div>

        <div class="b2-main">
          {{-- toolbar: count + filter button + sort --}}
          <div class="b2-toolbar">
            <button type="button" class="b2-fbtn" id="b2FilterBtn" aria-expanded="false" aria-controls="b2Filters">
              @include('pages.partials.b2b-icon', ['name' => 'sliders'])
              <span>{{ $tOr('b2b.filters.title', 'Filtrlər') }}</span>
              @if ($activeFilterCount > 0)
                <span class="n">{{ $activeFilterCount }}</span>
              @endif
            </button>

            <p class="b2-count">{{ $tOr('b2b.dir.count', ':count şirkət', ['count' => $companies->total()]) }}</p>

            <div class="b2-select b2-sort">
              <label class="sr-only" for="b2Sort">{{ $tOr('b2b.sort.label', 'Sıralama') }}</label>
              <select id="b2Sort" name="sort" data-autosubmit>
                @foreach ($sortOptions as $value => $label)
                  <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @include('pages.partials.b2b-icon', ['name' => 'chevron-down'])
            </div>
          </div>

          @if ($companies->isEmpty())
            @php $emptyNone = ! $hasCriteria; @endphp
            <div class="b2-empty">
              <span class="b2-empty-ic">@include('pages.partials.b2b-icon', ['name' => $emptyNone ? 'building' : 'search', 'stroke' => '1.6'])</span>
              @if ($emptyNone)
                <h3>{{ $tOr('b2b.empty.none_title', 'Hazırda şirkət yoxdur') }}</h3>
                <p>{{ $tOr('b2b.empty.none_text', 'Yeni şirkətlər əlavə olunan kimi burada görünəcək. Bir az sonra yenidən yoxlayın.') }}</p>
              @else
                <h3>{{ $tOr('b2b.empty.filtered_title', 'Şirkət tapılmadı') }}</h3>
                <p>{{ $tOr('b2b.empty.filtered_text', 'Axtarışınıza və seçdiyiniz filtrlərə uyğun şirkət yoxdur. Filtrləri sıfırlayın və ya axtarış sözünü dəyişin.') }}</p>
                <a class="b2-btn b2-btn-outline" href="{{ $resetUrl }}">@include('pages.partials.b2b-icon', ['name' => 'reset'])<span>{{ $tOr('b2b.empty.reset', 'Filtrləri sıfırla') }}</span></a>
              @endif
            </div>
          @else
            <div class="b2-grid">
              @foreach ($companies as $company)
                @include('pages.partials.b2b-company-card', ['company' => $company, 'tOr' => $tOr])
              @endforeach
            </div>

            @if ($companies->hasPages())
              <div class="b2-pagination">
                {{ $companies->links('pages.partials.tender-pagination') }}
              </div>
            @endif
          @endif
        </div>{{-- /.b2-main --}}
      </div>{{-- /.b2-body --}}
    </form>
  </div></div>

  <p class="sr-only" id="b2Live" aria-live="polite"></p>
</main>

@if ($showPricing)
{{-- ============================ CONSULTATION / LEAD MODAL ============================ --}}
<x-ui.modal id="b2LeadModal" class="b2-modal" :close-label="t('common.close')" aria-labelledby="b2LeadTitle"
            dialog="b2-modal-dialog">
  <div data-lead-step="form">
    <h2 class="b2-modal-title" id="b2LeadTitle">{{ $tOr('b2b.modal.title', 'B2B planı üçün müraciət') }}</h2>
    <p class="b2-modal-sub">{{ $tOr('b2b.modal.subtitle', 'Əlaqə məlumatlarınızı göndərin — komandamız seçdiyiniz plan üzrə sizinlə əlaqə saxlayacaq.') }}</p>

    <form class="b2-lead-form" id="b2LeadForm" action="{{ route('consultation-requests.store') }}" method="post" novalidate
          data-prefill="{{ $tOr('b2b.lead.prefill', 'B2B — :plan planı ilə maraqlanıram.', ['plan' => '__PLAN__']) }}"
          data-error="{{ t('common.error_generic') }}">
      @csrf
      <input type="hidden" name="source" value="b2b">
      <input type="hidden" name="b2b_plan" value="" data-lead-plankey>
      <div class="b2-lead-plan" data-lead-planbox hidden>
        <span class="k">{{ $tOr('b2b.modal.plan_label', 'Seçilmiş plan') }}</span>
        <span class="v" data-lead-planname></span>
      </div>

      <div class="ui-field">
        <label class="b2-label" for="b2LeadName">{{ $tOr('b2b.form.name', 'Ad, soyad') }} <span class="req" aria-hidden="true">*</span></label>
        <input class="b2-input" id="b2LeadName" name="full_name" type="text" required maxlength="120" autocomplete="name">
      </div>
      <div class="ui-field">
        <label class="b2-label" for="b2LeadPhone">{{ $tOr('b2b.form.phone', 'Telefon') }} <span class="req" aria-hidden="true">*</span></label>
        <input class="b2-input" id="b2LeadPhone" name="phone" type="tel" required maxlength="25" autocomplete="tel" placeholder="+994 XX XXX XX XX">
      </div>
      <div class="ui-field">
        <label class="b2-label" for="b2LeadMessage">{{ $tOr('b2b.form.message', 'Mesaj') }}</label>
        <textarea class="b2-input" id="b2LeadMessage" name="message" rows="3" maxlength="2000"
                  placeholder="{{ $tOr('b2b.form.message_placeholder', 'Əlavə suallarınız və ya qeydləriniz (istəyə bağlı)') }}"></textarea>
      </div>

      <p class="b2-lead-error" data-lead-error hidden role="alert"></p>

      <button type="submit" class="b2-btn b2-btn-primary b2-lead-submit">{{ $tOr('b2b.form.submit', 'Müraciəti göndər') }}</button>
      <p class="b2-lead-note">{{ $tOr('b2b.modal.note', 'Müraciət göndərdikdən sonra komandamız sizinlə əlaqə saxlayacaq.') }}</p>
    </form>
  </div>

  <div class="b2-lead-done" data-lead-step="done" hidden>
    <span class="b2-lead-done-ic">@include('pages.partials.b2b-icon', ['name' => 'check', 'stroke' => '2'])</span>
    <h2 class="b2-modal-title">{{ $tOr('b2b.done.title', 'Müraciət göndərildi') }}</h2>
    <p class="b2-modal-sub" data-lead-done-text>{{ $tOr('b2b.done.text', 'Müraciətiniz qəbul edildi. Komandamız tezliklə sizinlə əlaqə saxlayacaq.') }}</p>
    <button type="button" class="b2-btn b2-btn-outline" data-modal-close>{{ $tOr('b2b.done.close', 'Bağla') }}</button>
  </div>
</x-ui.modal>
@endif

</x-layout>
