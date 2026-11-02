{{--
  B2B company profile (Figma «10 · B2B BEYNƏLXALQ / ArchiAds» — no dedicated frame;
  built in the tender-detail language). Two columns on desktop, stacked on mobile.
  Rendered by B2bController@show. Every block renders only when its field has content.
  Admin field → block: phone/whatsapp/telegram/email/website/working_hours/legal_address
  → contact list; contact_person/position/contact_phone (if ≠ phone) → person block;
  instagram/linkedin/facebook → social row; market/sort_order are placement-only.
  Styles: resources/css/pages/b2b.css (.b2c-*)   JS: resources/js/pages/b2b.js
--}}
@php
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $coop = $company->cooperation_type?->translated();
  $images = $company->imageUrls();
  $languages = $company->languageLabels();
  $plan = $company->plan;
  $description = trim((string) $company->description);
  $catalogDesc = trim((string) $company->catalog_description);

  // "Kataloq / əməkdaşlıq" facts — only the rows that have a value.
  $facts = array_filter([
      ['icon' => 'layers', 'label' => $tOr('b2b.facts.category', 'Kateqoriya'), 'value' => $company->category?->name],
      ['icon' => 'tag', 'label' => $tOr('b2b.facts.brands', 'Brendlər'), 'value' => $company->brands],
      ['icon' => 'box', 'label' => $tOr('b2b.facts.moq', 'Minimum sifariş (MOQ)'), 'value' => $company->moq],
      ['icon' => 'briefcase', 'label' => $tOr('b2b.facts.cooperation', 'Əməkdaşlıq növü'), 'value' => $coop],
  ], fn (array $row): bool => filled($row['value']));

  // contact rows — only those with a value. `external` rows open in a new tab;
  // every href comes from a model helper that only emits tel:/mailto:/http(s).
  $websiteUrl = $company->websiteUrl();
  $directPhone = $company->directPhone();
  $primaryCta = $company->whatsappUrl() ?? $company->mailtoUrl() ?? $websiteUrl;
  $contactRows = array_filter([
      ['icon' => 'whatsapp', 'label' => $tOr('b2b.contact.whatsapp', 'WhatsApp'), 'value' => $company->whatsapp, 'href' => $company->whatsappUrl(), 'external' => true],
      ['icon' => 'phone', 'label' => $tOr('b2b.contact.phone', 'Telefon'), 'value' => $company->phone, 'href' => \App\Models\B2bCompany::telUrl($company->phone), 'external' => false],
      ['icon' => 'telegram', 'label' => $tOr('b2b.contact.telegram', 'Telegram'), 'value' => $company->telegram, 'href' => $company->socialUrl('telegram'), 'external' => true],
      ['icon' => 'mail', 'label' => $tOr('b2b.contact.email', 'E-poçt'), 'value' => $company->email, 'href' => $company->mailtoUrl(), 'external' => false],
      ['icon' => 'link', 'label' => $tOr('b2b.contact.website', 'Veb-sayt'), 'value' => $company->website, 'href' => $websiteUrl, 'external' => true],
      ['icon' => 'clock', 'label' => $tOr('b2b.contact.hours', 'İş saatları'), 'value' => $company->working_hours, 'href' => null, 'external' => false],
      ['icon' => 'pin', 'label' => $tOr('b2b.contact.address', 'Hüquqi ünvan'), 'value' => $company->legal_address, 'href' => null, 'external' => false],
  ], fn (array $row): bool => filled($row['value']));

  $socials = array_filter([
      ['icon' => 'instagram', 'label' => 'Instagram', 'href' => $company->socialUrl('instagram')],
      ['icon' => 'linkedin', 'label' => 'LinkedIn', 'href' => $company->socialUrl('linkedin')],
      ['icon' => 'facebook', 'label' => 'Facebook', 'href' => $company->socialUrl('facebook')],
  ], fn (array $row): bool => filled($row['href']));

  $hasMain = $description !== '' || $images !== [] || $facts !== [] || $catalogDesc !== '' || $languages !== [];
  $hasPerson = filled($company->contact_person) || $directPhone;
  $hasContact = $hasPerson || $contactRows !== [] || $socials !== [] || $primaryCta || filled($company->email);
  // The legal name is the h1 already when there is no brand name — don't repeat it.
  $showLegalName = filled($company->brand_name) && $company->brand_name !== $company->legal_name;
@endphp
<x-layout page="b2b-company" :title="$tOr('b2b.meta.company_title', ':name — ARCHİ B2B', ['name' => $company->displayName()])">

<main class="b2-page b2c" data-copied-label="{{ $tOr('b2b.contact.copied', 'Kopyalandı') }}">
  <div class="wrap"><div class="inner b2c-stack">

    <x-ui.breadcrumbs class="b2-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $tOr('b2b.crumb', 'B2B'), 'href' => route('b2b.index')],
        ['label' => $company->displayName()],
    ]" />

    <a class="b2c-back" href="{{ route('b2b.index') }}">
      @include('pages.partials.b2b-icon', ['name' => 'arrow-left'])
      <span>{{ $tOr('b2b.back', 'Bütün şirkətlər') }}</span>
    </a>

    {{-- header --}}
    <header class="b2c-head">
      @if ($company->logo_url)
        <img class="b2-logo b2c-logo" src="{{ $company->logo_url }}" alt="{{ $company->displayName() }}">
      @else
        <span class="b2-logo b2c-logo" aria-hidden="true">{{ $company->initials() }}</span>
      @endif

      <div class="b2c-head-main">
        @if ($company->is_verified || $company->is_featured || $plan?->badge())
        <div class="b2c-badges">
          @if ($company->is_verified)
            <span class="b2-badge b2-badge-ok">@include('pages.partials.b2b-icon', ['name' => 'badge-check', 'stroke' => '1.6'])<span>{{ $tOr('b2b.badge.verified', 'Təsdiqlənib') }}</span></span>
          @endif
          @if ($company->is_featured)
            <span class="b2-badge b2-badge-feat">@include('pages.partials.b2b-icon', ['name' => 'star', 'stroke' => '1.4'])<span>{{ $tOr('b2b.badge.featured', 'Seçilmiş') }}</span></span>
          @endif
          @if ($plan?->badge())
            <span class="b2-badge b2-badge-plan">{{ $tOr('b2b.plan_tag.'.$plan->value, $plan->label()) }}</span>
          @endif
        </div>
        @endif

        <h1>{{ $company->displayName() }}</h1>

        @if ($company->country || $coop)
        <div class="b2c-head-chips">
          @if ($company->country)
            <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'pin'])<span>{{ $company->country }}</span></span>
          @endif
          @if ($coop)
            <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'briefcase'])<span>{{ $coop }}</span></span>
          @endif
        </div>
        @endif

        @if ($showLegalName || filled($company->registration_no))
          <p class="b2c-legal">
            @if ($showLegalName)<span>{{ $company->legal_name }}</span>@endif
            @if (filled($company->registration_no))<span>{{ $tOr('b2b.facts.reg_no', 'Qeydiyyat №: :no', ['no' => $company->registration_no]) }}</span>@endif
          </p>
        @endif
      </div>
    </header>

    @if ($hasMain || $hasContact)
    <div class="b2c-cols">
      @if ($hasMain)
      <div class="b2c-colmain">
        @if ($description !== '')
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.about.title', 'Şirkət haqqında') }}</h2>
            <p class="b2c-text">{!! nl2br(e($description)) !!}</p>
          </section>
        @endif

        @if ($images !== [])
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.gallery.title', 'Kataloq / şəkillər') }}</h2>
            <div class="b2c-gallery">
              @foreach ($images as $src)
                <a class="b2c-shot" href="{{ $src }}" target="_blank" rel="noopener">
                  <img src="{{ $src }}" alt="{{ $tOr('b2b.gallery.alt', ':name kataloq şəkli', ['name' => $company->displayName()]) }}" loading="lazy">
                </a>
              @endforeach
            </div>
          </section>
        @endif

        @if ($facts !== [] || $catalogDesc !== '')
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.facts.title', 'Kataloq / əməkdaşlıq') }}</h2>
            @if ($facts !== [])
              <dl class="b2c-facts">
                @foreach ($facts as $row)
                  <div>
                    <dt>@include('pages.partials.b2b-icon', ['name' => $row['icon'], 'stroke' => '1.7']){{ $row['label'] }}</dt>
                    <dd>{{ $row['value'] }}</dd>
                  </div>
                @endforeach
              </dl>
            @endif
            @if ($catalogDesc !== '')
              <p class="b2c-text b2c-catalog-desc">{!! nl2br(e($catalogDesc)) !!}</p>
            @endif
          </section>
        @endif

        @if ($languages !== [])
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.languages.title', 'Ünsiyyət dilləri') }}</h2>
            <div class="b2c-langs">
              @foreach ($languages as $lang)
                <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'languages', 'stroke' => '1.7'])<span>{{ $lang }}</span></span>
              @endforeach
            </div>
          </section>
        @endif
      </div>
      @endif

      @if ($hasContact)
      <aside class="b2c-side">
        <section class="b2c-card b2c-contact">
          <h2>{{ $tOr('b2b.contact.title', 'Əlaqə') }}</h2>

          @if ($hasPerson)
            <div class="b2c-person">
              @include('pages.partials.b2b-icon', ['name' => 'user'])
              <div>
                @if (filled($company->contact_person))
                  <p class="n">{{ $company->contact_person }}</p>
                @endif
                @if (filled($company->contact_position))
                  <p class="o">{{ $company->contact_position }}</p>
                @endif
                @if ($directPhone)
                  <p class="p">
                    <span class="sr-only">{{ $tOr('b2b.contact.direct_phone', 'Birbaşa telefon') }}:</span>
                    @if ($tel = \App\Models\B2bCompany::telUrl($directPhone))
                      <a href="{{ $tel }}">{{ $directPhone }}</a>
                    @else
                      <span>{{ $directPhone }}</span>
                    @endif
                  </p>
                @endif
              </div>
            </div>
          @endif

          @if ($contactRows !== [])
            <ul class="b2c-lines">
              @foreach ($contactRows as $row)
                <li>
                  @include('pages.partials.b2b-icon', ['name' => $row['icon'], 'stroke' => '1.7'])
                  <span class="sr-only">{{ $row['label'] }}:</span>
                  @if ($row['href'])
                    <a href="{{ $row['href'] }}" @if ($row['external']) target="_blank" rel="noopener noreferrer" @endif>{{ $row['value'] }}</a>
                  @else
                    <span>{{ $row['value'] }}</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif

          @if ($socials !== [])
            <div class="b2c-social">
              @foreach ($socials as $s)
                <a href="{{ $s['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $s['label'] }}">@include('pages.partials.b2b-icon', ['name' => $s['icon'], 'stroke' => '1.6'])</a>
              @endforeach
            </div>
          @endif

          @if ($primaryCta || $company->email)
            <div class="b2c-actions">
              @if ($primaryCta)
                <a class="b2-btn b2-btn-primary" href="{{ $primaryCta }}" @if (! str_starts_with($primaryCta, 'mailto:')) target="_blank" rel="noopener noreferrer" @endif>{{ $tOr('b2b.contact.cta', 'Əlaqə saxla') }}</a>
              @endif
              @if ($company->email)
                <button type="button" class="b2-btn b2-btn-outline b2c-copy" data-copy="{{ $company->email }}"
                        data-copied="{{ $tOr('b2b.contact.copied', 'Kopyalandı') }}">
                  @include('pages.partials.b2b-icon', ['name' => 'copy'])
                  <span class="lbl">{{ $tOr('b2b.contact.copy_email', 'E-poçtu kopyala') }}</span>
                </button>
              @endif
            </div>
          @endif
        </section>
      </aside>
      @endif
    </div>
    @endif

    @if ($related->isNotEmpty())
      <section class="b2c-related">
        <h2>{{ $tOr('b2b.related.title', 'Oxşar şirkətlər') }}</h2>
        <div class="b2-grid">
          @foreach ($related as $relatedCompany)
            @include('pages.partials.b2b-company-card', ['company' => $relatedCompany, 'tOr' => $tOr])
          @endforeach
        </div>
      </section>
    @endif

    <p class="sr-only" id="b2Live" aria-live="polite"></p>
  </div></div>
</main>

</x-layout>
